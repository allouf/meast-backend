<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOfferAvailableSlot extends Model
{
    protected $fillable = [
        'service_offer_id',
        'day',
        'start_time',
        'end_time',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function serviceOffer()
    {
        return $this->belongsTo(ServiceOffer::class);
    }
}
