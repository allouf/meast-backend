<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Services\Enums\BoostCampaignStatus;

// use Modules\Services\Database\Factories\BoostCampaignFactory;

class BoostCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_offer_id',
        'provider_id',
        'boost_plan_id',
        'start_at',
        'end_at',
        'status',
        'targeting_scope',
        'amount',
        'currency',
        'terminated_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'terminated_at' => 'datetime',
        'targeting_scope' => 'array',
        'amount' => 'decimal:2',
        'status' => BoostCampaignStatus::class,

    ];

    public function plan()
    {
        return $this->belongsTo(BoostPlan::class);
    }

    public function offer()
    {
        return $this->belongsTo(ServiceOffer::class, 'service_offer_id');
    }

    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function logs()
    {
        return $this->hasMany(BoostCampaignLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === BoostCampaignStatus::Active
            && $this->start_at
            && $this->end_at
            && now()->between($this->start_at, $this->end_at);
    }
}
