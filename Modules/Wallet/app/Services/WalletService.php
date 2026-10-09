<?php

namespace Modules\Wallet\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Wallet\Enums\WalletTransactionType;
use Modules\Wallet\Exceptions\InsufficientFundsException;
use Modules\Wallet\Exceptions\InsufficientLockedFundsException;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Repositories\Interfaces\WalletRepositoryInterface;
use Modules\Wallet\Support\Amount;

/**
 * Application-level wallet operations.
 *
 * Identity / read (Feature 1):
 *   - getOrCreate
 *   - getBalances
 *
 * Mutations (Feature 2):
 *   - credit  : adds to available
 *   - debit   : subtracts from available (rejects if insufficient)
 *   - lock    : moves available -> locked (rejects if insufficient)
 *   - unlock  : moves locked -> available (rejects if insufficient locked)
 *
 * Each mutation:
 *   1. Validates the amount via Amount::normalize (throws InvalidAmountException).
 *   2. Resolves (or creates) the user's wallet.
 *   3. Hands a closure to the repository's mutate primitive, which runs it
 *      under a row lock inside a DB transaction. The closure mutates the
 *      relevant balance(s) and returns the audit-row payload.
 *   4. The repository writes the WalletTransaction audit row in the same
 *      transaction as the balance change, then commits.
 *
 * If the closure throws (e.g. InsufficientFundsException), the whole
 * transaction rolls back — no partial state is ever visible.
 *
 * Each public method returns the WalletTransaction that was written, so
 * callers can read balance_before / balance_after, the audit id for logging,
 * etc.
 */
class WalletService
{
    /**
     * Default denomination unit for newly-created wallets.
     * Will move to config once Checklist Row #1 (peg vs volatile MEEC) is settled.
     */
    private const DEFAULT_CURRENCY = 'USD';

    public function __construct(
        private readonly WalletRepositoryInterface $wallets,
    ) {
    }

    /**
     * Identity: return the user's wallet, creating it on first call.
     */
    public function getOrCreate(int $userId): Wallet
    {
        return $this->wallets->getOrCreateForUser($userId, self::DEFAULT_CURRENCY);
    }

    /**
     * Read: convenience accessor for the three balances.
     *
     * @return array{available:string,pending:string,locked:string,currency:string}
     */
    public function getBalances(int $userId): array
    {
        $wallet = $this->getOrCreate($userId);

        return [
            'available' => $wallet->available_balance,
            'pending'   => $wallet->pending_balance,
            'locked'    => $wallet->locked_balance,
            'currency'  => $wallet->currency,
        ];
    }

    /**
     * Read: paginated list of the user's wallet transactions, newest first.
     * Creates the wallet on first call if it doesn't yet exist (will return
     * an empty page in that case).
     */
    public function getTransactionsPaginated(int $userId, int $perPage = 15, ?string $type = null): LengthAwarePaginator
    {
        $wallet = $this->getOrCreate($userId);

        return $this->wallets->paginateTransactions($wallet->id, $perPage, $type);
    }

    /**
     * Add funds to the available balance.
     * Used for: deposits, refunds, manual top-ups.
     *
     * @param array{type:string,id:int}|null $reference Optional source entity link.
     * @param array<string,mixed>|null $metadata Optional free-form context.
     */
    public function credit(
        int $userId,
        string $amount,
        ?array $reference = null,
        ?array $metadata = null,
    ): WalletTransaction {
        $normalized = Amount::normalize($amount);
        $wallet = $this->getOrCreate($userId);

        return $this->wallets->mutate($wallet->id, function (Wallet $w) use ($normalized, $reference, $metadata) {
            $before = $w->available_balance;
            $after  = Amount::add($before, $normalized);

            $w->available_balance = $after;

            return [
                'type'           => WalletTransactionType::Credit->value,
                'amount'         => $normalized,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => $reference['type'] ?? null,
                'reference_id'   => $reference['id'] ?? null,
                'metadata'       => $metadata,
            ];
        });
    }

    /**
     * Subtract funds from the available balance.
     * Used for: platform-fee payments, withdrawals.
     *
     * @throws InsufficientFundsException if available < amount
     */
    public function debit(
        int $userId,
        string $amount,
        ?array $reference = null,
        ?array $metadata = null,
    ): WalletTransaction {
        $normalized = Amount::normalize($amount);
        $wallet = $this->getOrCreate($userId);

        return $this->wallets->mutate($wallet->id, function (Wallet $w) use ($normalized, $reference, $metadata) {
            $before = $w->available_balance;

            if (! Amount::gte($before, $normalized)) {
                throw new InsufficientFundsException($w->id, $normalized, $before);
            }

            $after = Amount::sub($before, $normalized);
            $w->available_balance = $after;

            return [
                'type'           => WalletTransactionType::Debit->value,
                'amount'         => $normalized,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => $reference['type'] ?? null,
                'reference_id'   => $reference['id'] ?? null,
                'metadata'       => $metadata,
            ];
        });
    }

    /**
     * Move funds from available to locked.
     * Used for: subscription / ad-package commitments where funds shouldn't
     * be spendable until the commitment ends.
     *
     * The audit row records balance_before / balance_after of the LOCKED
     * bucket (not available) — locked is what changed semantically; the
     * available decrement is its mirror.
     *
     * @throws InsufficientFundsException if available < amount
     */
    public function lock(
        int $userId,
        string $amount,
        ?array $reference = null,
        ?array $metadata = null,
    ): WalletTransaction {
        $normalized = Amount::normalize($amount);
        $wallet = $this->getOrCreate($userId);

        return $this->wallets->mutate($wallet->id, function (Wallet $w) use ($normalized, $reference, $metadata) {
            if (! Amount::gte($w->available_balance, $normalized)) {
                throw new InsufficientFundsException($w->id, $normalized, $w->available_balance);
            }

            $lockedBefore = $w->locked_balance;
            $lockedAfter  = Amount::add($lockedBefore, $normalized);

            $w->available_balance = Amount::sub($w->available_balance, $normalized);
            $w->locked_balance    = $lockedAfter;

            return [
                'type'           => WalletTransactionType::Lock->value,
                'amount'         => $normalized,
                'balance_before' => $lockedBefore,
                'balance_after'  => $lockedAfter,
                'reference_type' => $reference['type'] ?? null,
                'reference_id'   => $reference['id'] ?? null,
                'metadata'       => $metadata,
            ];
        });
    }

    /**
     * Apply a refund: subtract the refunded amount from available_balance.
     *
     * UNLIKE debit(), this method ALLOWS the resulting balance to be
     * negative. A refund is enforced by the gateway (Stripe) — we cannot
     * refuse it, and the user may have already consumed the topped-up
     * funds before the refund was issued. A negative balance accurately
     * records that the user owes the platform.
     *
     * The audit row is written with type=REFUND (not DEBIT) so reporting,
     * UI, and reconciliation can distinguish refund-driven outflows from
     * normal user spending.
     *
     * Caller is expected to perform any business-rule checks (e.g.,
     * "should we refund only what's available, write off the rest as
     * platform loss?") before calling this — by the time we get here, we
     * commit the full refund amount to the ledger.
     *
     * @param array{type:string,id:int}|null $reference Optional source entity link (typically InvoiceRefund).
     * @param array<string,mixed>|null $metadata Optional free-form context (Stripe ids, reason, etc.).
     */
    public function refund(
        int $userId,
        string $amount,
        ?array $reference = null,
        ?array $metadata = null,
    ): WalletTransaction {
        $normalized = Amount::normalize($amount);
        $wallet = $this->getOrCreate($userId);

        return $this->wallets->mutate($wallet->id, function (Wallet $w) use ($normalized, $reference, $metadata) {
            $before = $w->available_balance;
            // Note: Amount::sub may return a negative result; we deliberately
            // do NOT guard against it here. Balance can go negative.
            $after  = Amount::sub($before, $normalized);

            $w->available_balance = $after;

            return [
                'type'           => WalletTransactionType::Refund->value,
                'amount'         => $normalized,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_type' => $reference['type'] ?? null,
                'reference_id'   => $reference['id'] ?? null,
                'metadata'       => $metadata,
            ];
        });
    }

    /**
     * Move funds from locked back to available.
     * Used when a subscription / commitment is cancelled or expires.
     *
     * The audit row records balance_before / balance_after of the LOCKED
     * bucket (mirroring `lock`).
     *
     * @throws InsufficientLockedFundsException if locked < amount
     */
    public function unlock(
        int $userId,
        string $amount,
        ?array $reference = null,
        ?array $metadata = null,
    ): WalletTransaction {
        $normalized = Amount::normalize($amount);
        $wallet = $this->getOrCreate($userId);

        return $this->wallets->mutate($wallet->id, function (Wallet $w) use ($normalized, $reference, $metadata) {
            if (! Amount::gte($w->locked_balance, $normalized)) {
                throw new InsufficientLockedFundsException($w->id, $normalized, $w->locked_balance);
            }

            $lockedBefore = $w->locked_balance;
            $lockedAfter  = Amount::sub($lockedBefore, $normalized);

            $w->locked_balance    = $lockedAfter;
            $w->available_balance = Amount::add($w->available_balance, $normalized);

            return [
                'type'           => WalletTransactionType::Unlock->value,
                'amount'         => $normalized,
                'balance_before' => $lockedBefore,
                'balance_after'  => $lockedAfter,
                'reference_type' => $reference['type'] ?? null,
                'reference_id'   => $reference['id'] ?? null,
                'metadata'       => $metadata,
            ];
        });
    }
}
