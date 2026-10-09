<?php

namespace Modules\Wallet\Enums;

/**
 * Domain-agnostic escrow lifecycle states. An escrow row represents funds
 * held by the platform (or held in a Stripe Connect destination account)
 * pending some domain condition (delivery accepted, dispute resolved, etc.).
 *
 * Lifecycle:
 *   Pending  → Paid     (rail confirms funds landed)
 *   Paid     → Released (domain condition met — release to seller)
 *   Paid     → Refunded (cancel before release; rail issues reversal)
 *   Paid     → Disputed (claim opened; release blocked pending resolution)
 *   Disputed → Released / Refunded (admin or dispute service resolves)
 *
 * Failed is a terminal state for rail-side issues (e.g. seller's Connect
 * account became ineligible between charge and intended release).
 *
 * Status string values mirror the legacy ServiceOfferOrderEscrowStatus so
 * existing rows on `service_offer_order_escrows` carry over without a data
 * migration on the `status` column.
 */
enum EscrowStatus: string
{
    case Pending = 'PENDING';
    case Paid = 'PAID';
    case Released = 'RELEASED';
    case Refunded = 'REFUNDED';
    case Disputed = 'DISPUTED';
    case Failed = 'FAILED';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
