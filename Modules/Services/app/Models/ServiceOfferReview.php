<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

class ServiceOfferReview extends Model
{
    protected $table = 'service_offer_reviews';

    protected $fillable = [
        'user_id',
        'service_offer_id',
        'rating',
        'description',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceOffer()
    {
        return $this->belongsTo(ServiceOffer::class);
    }
}
