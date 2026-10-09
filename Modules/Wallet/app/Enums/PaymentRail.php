<?php

namespace Modules\Wallet\Enums;

/**
 * Identifier for the payment provider that handled (or will handle) an invoice.
 *
 * Stored on `invoices.rail` as a string. Strings (not strict enum-only) so we
 * can introduce a new rail without ALTER TABLE — just add a case here and a
 * concrete `PaymentRailInterface` implementation.
 *
 * Rails currently planned:
 *   - Stripe        : card payment, user→platform (existing top-up flow)
 *   - StripeConnect : card payment, marketplace flow (split between seller and platform)
 *   - PlatformCredit: instant debit from `wallets` (no external provider)
 *   - NowPayments   : crypto payment, BTC/ETH/USDT/BNB user→platform (Phase 4b)
 *   - Transak       : already integrated for token purchase, NOT used as a payment rail
 *                     for platform invoices — it produces token transfers, not invoice settlement
 *   - MoonPay       : alternative card on-ramp for token purchase (Phase 4b, same as Transak's role)
 */
enum PaymentRail: string
{
    case Stripe          = 'stripe';
    case StripeConnect   = 'stripe_connect';
    case PlatformCredit  = 'platform_credit';
    case NowPayments     = 'nowpayments';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Whether this rail settles instantly inside the platform without
     * any redirect. PlatformCredit is the only such rail currently —
     * PaymentInitiator can mark the invoice Completed synchronously and
     * fire PaymentSucceeded inside the initiate() call.
     */
    public function isInstant(): bool
    {
        return $this === self::PlatformCredit;
    }
}
