<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit entry for a state change on an Invoice.
 *
 * Created from inside the Invoice's state-transition methods (typically
 * within the same DB transaction as the status update). Application code
 * NEVER updates or deletes rows here — finance and dispute handling rely
 * on the log being immutable.
 *
 * @property int                $id
 * @property int                $invoice_id
 * @property string             $event_type        e.g. 'created', 'status_changed', 'refunded'
 * @property string|null        $from_status
 * @property string|null        $to_status
 * @property string             $actor_type        'system' | 'webhook' | 'user' | 'admin'
 * @property int|null           $actor_id
 * @property array|null         $payload           free-form JSON context
 * @property \Illuminate\Support\Carbon  $created_at
 */
class InvoiceAuditLog extends Model
{
    protected $table = 'invoice_audit_log';

    /**
     * No `updated_at` — rows are never modified after insert.
     */
    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
