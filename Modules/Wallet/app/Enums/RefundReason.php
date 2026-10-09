<?php

namespace Modules\Wallet\Enums;

/**
 * Reason categorisation for a refund.
 *
 * Required for finance reporting (chargebacks vs voluntary refunds), fraud
 * monitoring, and ops triage. Values are stored on `invoice_refunds.reason`
 * as strings so we can extend without ALTER TABLE.
 *
 * Mapping notes:
 *   - 'requested_by_customer' — Stripe's reason of the same name; voluntary
 *     refund from buyer-side (changed mind, dissatisfied).
 *   - 'duplicate'             — Stripe's 'duplicate'; usually buyer charged
 *     twice by mistake.
 *   - 'fraudulent'            — Stripe's 'fraudulent'; card-not-present
 *     fraud detected after charge.
 *   - 'chargeback'            — buyer's bank initiated dispute; we don't
 *     control these — Stripe forces the reversal and we record it.
 *   - 'seller_failed_to_deliver' — for marketplace flow: seller didn't ship
 *     / didn't complete the work. Our domain logic (or admin) decides this.
 *   - 'platform_compensation' — platform issuing goodwill refund unrelated
 *     to provider error.
 *   - 'other'                 — fallback; should be rare. Use the InvoiceRefund's
 *     `note` column to explain.
 */
enum RefundReason: string
{
    case RequestedByCustomer    = 'requested_by_customer';
    case Duplicate              = 'duplicate';
    case Fraudulent             = 'fraudulent';
    case Chargeback             = 'chargeback';
    case SellerFailedToDeliver  = 'seller_failed_to_deliver';
    case PlatformCompensation   = 'platform_compensation';
    case Other                  = 'other';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Map a rail-provided reason string into our enum (best-effort).
     * Stripe's reason values map directly; unknown reasons fall to Other.
     */
    public static function fromRailString(?string $rawReason): self
    {
        return match ($rawReason) {
            'requested_by_customer' => self::RequestedByCustomer,
            'duplicate'             => self::Duplicate,
            'fraudulent'            => self::Fraudulent,
            'chargeback'            => self::Chargeback,
            default                 => self::Other,
        };
    }
}
