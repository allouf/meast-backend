<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Wallet\Enums\RefundReason;

/**
 * One row per refund processed against an Invoice.
 *
 * Replaces the previous `WalletRefund` model (same table, renamed and
 * generalised). A single invoice can have multiple partial refunds, each
 * with its own row; full refunds are also recorded here AND additionally
 * trip the parent invoice's status to Refunded.
 *
 * Idempotency: anchored on `partner_refund_id` (unique). If a rail's
 * webhook delivers the same refund event twice, the second insert hits
 * the unique constraint and we skip.
 *
 * @property int                $id
 * @property int                $invoice_id
 * @property int                $wallet_id           buyer's wallet (when applicable)
 * @property string             $amount              positive (direction implied by entity)
 * @property string             $currency
 * @property string|null        $partner_refund_id   rail's id for this refund
 * @property RefundReason       $reason
 * @property array|null         $metadata            rail-specific extras (stripe_charge_id, etc.)
 * @property int|null           $wallet_transaction_id   audit row produced when applying the refund
 */
class InvoiceRefund extends Model
{
    protected $fillable = [
        'invoice_id',
        'wallet_id',
        'amount',
        'currency',
        'partner_refund_id',
        'reason',
        'metadata',
        'wallet_transaction_id',
    ];

    protected $casts = [
        'amount'   => 'decimal:8',
        'reason'   => RefundReason::class,
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'wallet_transaction_id');
    }
}
