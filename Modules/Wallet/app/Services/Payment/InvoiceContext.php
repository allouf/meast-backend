<?php

namespace Modules\Wallet\Services\Payment;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable carrier for the inputs to PaymentInitiator::initiate().
 *
 * Used in two places:
 *   - Inside RailResolver — rails introspect the context to decide if they
 *     can handle this combination (kind / seller presence / amount / ...).
 *   - Inside PaymentInitiator — passed to the resolved rail's initiate(Invoice)
 *     after the row is persisted.
 *
 * Pure data, no behaviour. Field semantics match the corresponding columns
 * on the `invoices` table; see Invoice model docblock for full descriptions.
 */
final class InvoiceContext
{
    public function __construct(
        public readonly string  $kind,
        public readonly int     $buyerUserId,
        public readonly ?int    $sellerUserId,
        public readonly string  $amount,
        public readonly string  $currency,
        public readonly ?Model  $referenceable,
        public readonly ?string $idempotencyKey,
        /** @var array<string, mixed> */
        public readonly array   $metadata,
        /** Optional rail hint. Frontend can suggest 'stripe' / 'platform_credit' / etc. */
        public readonly ?string $preferredRail = null,
    ) {
    }

    /** Convenience: marketplace flow is when seller is set. */
    public function isMarketplace(): bool
    {
        return $this->sellerUserId !== null;
    }
}
