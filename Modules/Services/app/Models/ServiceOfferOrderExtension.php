<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Services\Enums\ServiceOfferOrderExtensionStatus;

// use Modules\Services\Database\Factories\ServiceOfferOrderExtensionFactory;

class ServiceOfferOrderExtension extends Model
{
    use HasFactory;

    protected $table = 'service_offer_order_extensions';

    protected $fillable = [
        'service_offer_order_id',
        'reason_id',
        'requested_days',
        'description',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'status' => ServiceOfferOrderExtensionStatus::class, 
        'responded_at' => 'datetime',
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

    public function reason()
    {
        return $this->belongsTo(ServiceOfferOrderExtensionReason::class, 'reason_id');
    }
}
