<?php

namespace Modules\Wallet\Services\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Contracts\PaymentInitiatorInterface;
use Modules\Wallet\Enums\InvoiceStatus;
use Modules\Wallet\Exceptions\PaymentInitiationException;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Services\WalletService;

/**
 * Concrete implementation of the public payment-initiation contract.
 *
 * Resolves rail → persists Invoice → invokes rail's initiate() → returns.
 * Wraps the persistence + rail call in a DB transaction so a rail failure
 * doesn't leave a stranded Pending row.
 *
 * Idempotency rules (in order):
 *   1. If $idempotencyKey was provided AND an invoice with that key exists,
 *      return it as-is (no rail call). This is the explicit
 *      retry-safe path the caller signs up for.
 *   2. If $referenceable was provided AND an existing Pending invoice
 *      points at it, return that pending invoice (no rail call). This is
 *      the implicit "user double-clicks Pay" guard.
 *   3. Otherwise create a new invoice, attach a rail, return.
 *
 * Validation:
 *   - Amount must parse as a positive decimal with ≤ 8 fractional digits.
 *   - Currency must be 3-letter ISO (case-insensitive).
 *   - kind must be non-empty.
 *
 * Out of scope (deferred):
 *   - Per-kind min/max enforcement (Phase 4b — config-driven once we have
 *     enough kinds to need it).
 *   - Frontend-supplied preferred-rail validation against an allowlist
 *     (Phase 4b).
 */
class PaymentInitiator implements PaymentInitiatorInterface
{
    public function __construct(
        private readonly RailResolver $resolver,
        private readonly WalletService $walletService,
    ) {
    }

    public function initiate(
        string $kind,
        int $buyerUserId,
        ?int $sellerUserId,
        string $amount,
        string $currency,
        ?Model $referenceable = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
        ?string $preferredRail = null,
    ): Invoice {
        $this->validate($kind, $amount, $currency);

        // Step 1 — explicit idempotency. If the caller passed a key and we
        // already have an invoice for it, return that invoice unchanged.
        if ($idempotencyKey !== null) {
            $existing = Invoice::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        // Step 2 — implicit concurrency guard. If a referenceable is given
        // and it has a Pending invoice already, return that one instead of
        // creating a duplicate. We do this inside a transaction with a
        // SELECT ... FOR UPDATE to prevent two concurrent initiate() calls
        // from both deciding "no pending exists" and creating two rows.
        return DB::transaction(function () use (
            $kind,
            $buyerUserId,
            $sellerUserId,
            $amount,
            $currency,
            $referenceable,
            $idempotencyKey,
            $metadata,
            $preferredRail,
        ) {
            if ($referenceable !== null) {
                $existing = Invoice::query()
                    ->where('referenceable_type', $referenceable::class)
                    ->where('referenceable_id', $referenceable->getKey())
                    ->where('status', InvoiceStatus::Pending->value)
                    ->lockForUpdate()
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }

            // Build the context for rail resolution.
            $ctx = new InvoiceContext(
                kind:            $kind,
                buyerUserId:     $buyerUserId,
                sellerUserId:    $sellerUserId,
                amount:          $amount,
                currency:        $currency,
                referenceable:   $referenceable,
                idempotencyKey:  $idempotencyKey,
                metadata:        $metadata,
                preferredRail:   $preferredRail,
            );

            $rail = $this->resolver->resolve($ctx);

            // Resolve / create the buyer's wallet up-front so we can store
            // its id on the invoice. Useful for any rail that later credits
            // the wallet (refunds), and for cross-referencing on the credit
            // ledger side.
            $wallet = $this->walletService->getOrCreate($buyerUserId);

            // Create the Invoice row in Pending state. The rail's initiate()
            // will then mutate it (attaching checkout_url + partner_reference_id
            // for non-instant rails, or transitioning it to Completed inline
            // for instant rails like PlatformCredit).
            //
            // expires_at: a default 24-hour window covers most rails. Stripe
            // Checkout sessions also expire after 24h on Stripe's side, so the
            // two timeouts roughly coincide. Future kinds that need shorter or
            // longer windows can override via metadata in Phase 4b config.
            $invoice = Invoice::create([
                'kind'                => $kind,
                'user_id'             => $buyerUserId,
                'seller_user_id'      => $sellerUserId,
                'wallet_id'           => $wallet->id,
                'amount'              => $amount,
                'currency'            => $currency,
                'status'              => InvoiceStatus::Pending->value,
                'rail'                => $rail->name()->value,
                'idempotency_key'     => $idempotencyKey,
                'metadata'            => $metadata ?: null,
                'referenceable_type'  => $referenceable ? $referenceable::class : null,
                'referenceable_id'    => $referenceable?->getKey(),
                'expires_at'          => now()->addDay(),
            ]);

            // Audit: initial 'created' entry.
            $invoice->auditLog()->create([
                'event_type' => 'created',
                'to_status'  => InvoiceStatus::Pending->value,
                'actor_type' => 'system',
                'payload'    => ['rail' => $rail->name()->value, 'preferred_rail' => $preferredRail],
                'created_at' => now(),
            ]);

            // Hand off to the rail. Wrapped so any provider error becomes
            // a PaymentInitiationException with the invoice already
            // rolled back by the surrounding transaction.
            return $rail->initiate($invoice);
        });
    }

    /**
     * Refund a Completed invoice through its original rail. See the
     * interface docblock for the full contract.
     *
     * Implementation: looks up the rail by name (each Invoice carries
     * the rail that handled it in the `rail` column), calls its
     * refund() method, then transitions the parent invoice to REFUNDED
     * if it was a FULL refund. Partial refunds leave the invoice
     * COMPLETED — the InvoiceRefund row itself is the audit.
     *
     * bccomp is used instead of `===` for the full-refund check so
     * "100" and "100.00000000" compare equal (decimal-string strict
     * equality would otherwise misfire).
     */
    public function refund(
        Invoice $invoice,
        string $amount,
        string $reason,
        string $actorType = 'system',
        ?int $actorId = null,
    ): InvoiceRefund {
        $rail   = $this->resolver->byName($invoice->rail);
        $refund = $rail->refund($invoice, $amount, $reason);

        if (bccomp($amount, (string) $invoice->amount, 8) === 0) {
            $invoice->markRefunded($refund, actorType: $actorType, actorId: $actorId);
        }

        return $refund;
    }

    private function validate(string $kind, string $amount, string $currency): void
    {
        if (trim($kind) === '') {
            throw new PaymentInitiationException('kind cannot be empty.');
        }

        if (! preg_match('/^\d+(\.\d{1,8})?$/', $amount) || (float) $amount <= 0) {
            throw new PaymentInitiationException(sprintf(
                'amount must be a positive decimal string with up to 8 fractional digits (got %s).',
                $amount,
            ));
        }

        if (! preg_match('/^[A-Za-z]{3}$/', $currency)) {
            throw new PaymentInitiationException(sprintf(
                'currency must be a 3-letter ISO code (got %s).',
                $currency,
            ));
        }
    }
}
