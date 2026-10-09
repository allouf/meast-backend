<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

class ServiceOfferReport extends Model
{
    protected $table = 'service_offer_reports';

    protected $fillable = [
        'user_id',
        'service_offer_id',
        'reason',
        'description',
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
