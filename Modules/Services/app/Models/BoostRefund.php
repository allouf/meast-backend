<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Services\Database\Factories\BoostRefundFactory;

class BoostRefund extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'boost_campaign_id',
        'amount',
        'currency',
        'type',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(BoostCampaign::class);
    }
}
