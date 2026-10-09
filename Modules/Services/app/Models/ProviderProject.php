<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderProject extends Model
{
    protected $fillable = [
        'service_provider_id',
        'title',
        'url',
        'description',
        'images',
        'sort_order',
    ];

    protected $casts = [
        'images' => 'array',
        'sort_order' => 'integer',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function serviceOffers()
    {
        return $this->belongsToMany(
            ServiceOffer::class,
            'provider_project_service_offer',
            'provider_project_id',
            'service_offer_id'
        )->withTimestamps();
    }
}
