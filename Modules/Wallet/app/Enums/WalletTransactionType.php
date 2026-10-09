<?php

namespace Modules\Wallet\Enums;

/**
 * Type of change recorded in a WalletTransaction row.
 *
 *   CREDIT — funds added to available balance (top-up, manual top-up).
 *   DEBIT  — funds removed from available balance (platform-fee payment).
 *   LOCK   — funds moved from available to locked (subscription / ad commitment hold).
 *   UNLOCK — funds moved from locked back to available (commitment released or expired).
 *   REFUND — funds removed from available balance because a previous payment was
 *            refunded by the gateway. Distinguished from DEBIT for reporting and
 *            UI clarity. May drive available_balance negative (the user has
 *            consumed services that were paid for via the now-refunded charge).
 *
 * Values follow the team's SCREAMING_SNAKE_CASE convention
 * (mirrors ServiceOfferOrderStatus, etc.).
 */
enum WalletTransactionType: string
{
    case Credit = 'CREDIT';
    case Debit  = 'DEBIT';
    case Lock   = 'LOCK';
    case Unlock = 'UNLOCK';
    case Refund = 'REFUND';

    /**
     * Returns the raw string values of all cases.
     * Useful for validation rules: Rule::in(WalletTransactionType::values())
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
