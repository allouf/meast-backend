<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Services\Enums\ServiceOfferOrderDisputeStatus;

// use Modules\Services\Database\Factories\ServiceOfferOrderDisputeFactory;

class ServiceOfferOrderDispute extends Model
{
    use HasFactory;

    protected $table = 'service_offer_order_disputes';

    protected $fillable = [
        'service_offer_order_id',
        'buyer_id',
        'provider_id',
        'opened_by', // buyer | provider
        'reason_id', // enum (late_delivery, bad_delivery, scope_creep...)
        'description',
        'status', // open, under_review, resolved, rejected
        'frozen_at',
        'resolved_at',
    ];

    protected $casts = [
        'status' => ServiceOfferOrderDisputeStatus::class,
        'frozen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(ServiceOfferOrder::class, 'service_offer_order_id');
    }

    public function buyer()
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'buyer_id');
    }

    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function reason()
    {
        return $this->belongsTo(DisputeReason::class, 'reason_id');
    }
    public function evidences()
    {
        return $this->hasMany(ServiceOfferOrderDisputeEvidence::class, 'dispute_id');
    }
}
