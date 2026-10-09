<?php

namespace Modules\Wallet\Enums;

/**
 * Lifecycle states for an Invoice row.
 *
 * Replaces the old WalletTopupIntentStatus. Same four canonical states
 * (Pending / Completed / Cancelled / Failed) plus two new ones:
 *
 *   Refunded — at least one refund has been processed against the invoice.
 *              Full refunds set the invoice to Refunded; partial refunds
 *              keep it at Completed and record the refund as a child row.
 *   Expired  — pending invoice exceeded its expires_at timestamp without
 *              the user completing payment. Distinguished from Cancelled
 *              (user-initiated) and Failed (payment-attempt error).
 *
 * Transition graph:
 *
 *   Pending ──▶ Completed   (rail webhook confirms payment)
 *           ──▶ Cancelled   (user cancels OR initiate() is called for the
 *                             same referenceable while a previous pending
 *                             invoice exists and we choose to cancel it)
 *           ──▶ Failed      (rail reports payment error)
 *           ──▶ Expired     (expires_at passed; expiration cron fires)
 *
 *   Completed ──▶ Refunded  (full refund)
 *
 * Once a row reaches Cancelled / Failed / Expired / Refunded it never
 * transitions again. Completed rows can still receive partial refunds
 * (recorded as InvoiceRefund children) without changing the invoice's
 * status.
 *
 * Values follow the team's SCREAMING_SNAKE_CASE convention so existing
 * top-up rows (which were written under the old enum) still match.
 */
enum InvoiceStatus: string
{
    case Pending   = 'PENDING';
    case Completed = 'COMPLETED';
    case Cancelled = 'CANCELLED';
    case Failed    = 'FAILED';
    case Expired   = 'EXPIRED';
    case Refunded  = 'REFUNDED';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Terminal states cannot be transitioned out of (the cron / webhook
     * handlers MUST short-circuit if they encounter one).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Cancelled, self::Failed, self::Expired, self::Refunded => true,
            default => false,
        };
    }
}
