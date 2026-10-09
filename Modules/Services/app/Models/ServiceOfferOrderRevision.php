<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Services\Enums\ServiceOfferOrderRevisionRequestType;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;

class ServiceOfferOrderRevision extends Model
{
    protected $table = 'service_offer_order_revisions';

    protected $fillable = [
        'service_offer_order_id',
        'service_offer_order_phase_id',
        'delivery_version',
        'notes',
        'request_type',
        'attachments',
        'status',
        'rejection_reason',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'request_type' => ServiceOfferOrderRevisionRequestType::class,
        'status' => ServiceOfferOrderRevisionStatus::class,
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOfferOrder::class, 'service_offer_order_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ServiceOfferOrderPhase::class, 'service_offer_order_phase_id');
    }
}
