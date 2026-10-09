<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Services\Database\Factories\ServiceOfferMediaFactory;

class ServiceOfferMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_offer_id',
        'media_path',
        'media_type',
        'is_cover',
        'sort_order',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function serviceOffer()
    {
        return $this->belongsTo(ServiceOffer::class);
    }

}
