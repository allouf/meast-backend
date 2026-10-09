<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Services\Enums\ServiceOfferOrderDisputeEvidenceType;

// use Modules\Services\Database\Factories\ServiceOfferOrderDisputeEvidenceFactory;

class ServiceOfferOrderDisputeEvidence extends Model
{
    use HasFactory;

    protected $table = 'service_offer_order_dispute_evidences';

    protected $fillable = [
        'dispute_id',
        'type', // revision | extension | delivery | message
        'reference_id',
        'data_snapshot',
    ];

    protected $casts = [
        'type' => ServiceOfferOrderDisputeEvidenceType::class,
        'data_snapshot' => 'array',
    ];

    public function dispute()
    {
        return $this->belongsTo(ServiceOfferOrderDispute::class, 'dispute_id');
    }

    public function isRevision(): bool
    {
        return $this->type === ServiceOfferOrderDisputeEvidenceType::Revision;
    }

    public function isDelivery(): bool
    {
        return $this->type === ServiceOfferOrderDisputeEvidenceType::Delivery;
    }

    public function isExtension(): bool
    {
        return $this->type === ServiceOfferOrderDisputeEvidenceType::Extension;
    }
}
