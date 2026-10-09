<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Wallet\Enums\EscrowStatus;

/**
 * Funds held by the platform pending a domain condition. Polymorphic on
 * `escrowable_type` + `escrowable_id` so any module that holds funds —
 * service orders, milestone phases, boost campaigns, future subscription
 * models — can attach escrow without schema changes.
 *
 * Owns a direct FK to the funding Invoice (`invoice_id`) so refund + audit
 * lookups don't need the legacy rail+partner_reference_id join. Status
 * lifecycle is domain-agnostic; see EscrowStatus.
 *
 * Physical table remains `service_offer_order_escrows` for now — a follow-up
 * commit will rename to `escrows` once we've confirmed no out-of-tree
 * references (raw SQL, BI queries, etc.) hardcode the old name.
 *
 * The legacy `service_offer_order_id` and `service_offer_order_phase_id`
 * columns are still present (left intact for one release cycle for rollback
 * safety) but no app code reads them — the polymorphic columns are
 * authoritative. Commit 5 drops them.
 *
 * @property int                            $id
 * @property string                         $escrowable_type
 * @property int                            $escrowable_id
 * @property int|null                       $invoice_id
 * @property string                         $gateway        Rail name, e.g. 'stripe_connect'
 * @property string                         $transaction_id Rail-side reference (partner_reference_id from Invoice)
 * @property string                         $amount         Decimal string
 * @property string                         $currency       ISO 4217
 * @property EscrowStatus                   $status
 * @property \Illuminate\Support\Carbon|null $paid_at
 */
class Escrow extends Model
{
    protected $table = 'service_offer_order_escrows';

    protected $fillable = [
        'escrowable_type',
        'escrowable_id',
        'invoice_id',
        'gateway',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'status'  => EscrowStatus::class,
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function escrowable(): MorphTo
    {
        return $this->morphTo();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
