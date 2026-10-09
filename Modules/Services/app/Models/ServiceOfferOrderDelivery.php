<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Services\Database\Factories\ServiceOfferOrderDeliveryFactory;

class ServiceOfferOrderDelivery extends Model
{
    use HasFactory;

    protected $table = 'service_offer_order_deliveries';

    protected $fillable = [
        'service_offer_order_id',
        'service_offer_order_phase_id',
        'version',
        'message',
        'attachments',
        'submitted_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'submitted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(ServiceOfferOrder::class, 'service_offer_order_id');
    }

    public function phase()
    {
        return $this->belongsTo(ServiceOfferOrderPhase::class, 'service_offer_order_phase_id');
    }
}
