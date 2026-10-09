<?php

namespace Modules\Wallet\Repositories\Eloquent;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Repositories\Interfaces\WalletRepositoryInterface;

/**
 * Eloquent implementation of WalletRepositoryInterface.
 *
 * Concurrency model:
 *   - getOrCreateForUser uses lockForUpdate on the user_id lookup so two
 *     concurrent first-time creates for the same user serialize and only
 *     one row is inserted.
 *   - mutate uses lockForUpdate on the wallet row so all balance changes
 *     for the same wallet serialize. Inside the lock, balances are read,
 *     the caller's closure decides the new state, and the audit row is
 *     written — all inside one DB transaction.
 *
 * The repository never validates business rules (e.g. "insufficient funds").
 * Validation lives in the closure passed by WalletService.
 */
class WalletRepository implements WalletRepositoryInterface
{
    public function __construct(private readonly Wallet $wallet)
    {
    }

    public function findByUserId(int $userId): ?Wallet
    {
        return $this->wallet->newQuery()
            ->where('user_id', $userId)
            ->first();
    }

    public function getOrCreateForUser(int $userId, string $currency): Wallet
    {
        return DB::transaction(function () use ($userId, $currency) {
            $existing = $this->wallet->newQuery()
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            return $this->wallet->newQuery()->create([
                'user_id'           => $userId,
                'available_balance' => 0,
                'pending_balance'   => 0,
                'locked_balance'    => 0,
                'currency'          => $currency,
            ]);
        });
    }

    public function mutate(int $walletId, Closure $apply): WalletTransaction
    {
        return DB::transaction(function () use ($walletId, $apply) {
            // Serialize concurrent mutations to the same wallet.
            $wallet = $this->wallet->newQuery()
                ->whereKey($walletId)
                ->lockForUpdate()
                ->firstOrFail();

            // Closure decides the new balance state (and may throw if invalid).
            // It returns the audit-row payload.
            $payload = $apply($wallet);

            // Persist the (possibly modified) wallet first so that if the
            // audit row insert fails, the transaction rolls back the balance
            // change too — they live or die together.
            $wallet->save();

            return WalletTransaction::query()->create([
                'wallet_id'      => $wallet->id,
                'type'           => $payload['type'],
                'amount'         => $payload['amount'],
                'balance_before' => $payload['balance_before'],
                'balance_after'  => $payload['balance_after'],
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id'   => $payload['reference_id'] ?? null,
                'metadata'       => $payload['metadata'] ?? null,
            ]);
        });
    }

    public function paginateTransactions(int $walletId, int $perPage, ?string $type = null): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->where('wallet_id', $walletId)
            ->when($type !== null, fn ($q) => $q->where('type', $type))
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
