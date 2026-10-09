<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Services\Enums\DeliveryTimeUnit;

class ServiceOfferPlan extends Model
{
    protected $fillable = [
        'service_offer_id',
        'title',
        'price',
        'currency',
        'delivery_time_value',
        'delivery_time_unit',
        'revision_window',
        'number_of_revisions',
        'revision_scope_rules',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'delivery_time_unit' => DeliveryTimeUnit::class,
        'number_of_revisions' => 'integer',
        'sort_order' => 'integer',
    ];

    public function serviceOffer()
    {
        return $this->belongsTo(ServiceOffer::class);
    }
}
