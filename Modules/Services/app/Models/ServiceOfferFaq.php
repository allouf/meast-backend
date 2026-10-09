<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOfferFaq extends Model
{
    protected $fillable = [
        'service_offer_id',
        'question',
        'answer',
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
