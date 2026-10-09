<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Services\Enums\BoostCampaignStatus;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Enums\DeliveryTimeUnit;
use Modules\Services\Enums\ServiceOfferStatus;
use Modules\Services\Enums\ServiceOfferType;

class ServiceOffer extends Model
{
    protected $fillable = [
        'service_provider_id',
        'service_category_id',
        'title',
        'type',
        'delivery_model',
        'description',
        'base_price',
        'base_currency',
        'base_delivery_time_value',
        'base_delivery_time_unit',
        'base_number_of_revisions',
        'base_revision_window',
        'milestone_available',
        'is_active',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'type' => ServiceOfferType::class,
        'delivery_model' => DeliveryModel::class,
        'base_price' => 'decimal:2',
        'base_delivery_time_value' => 'integer',
        'base_delivery_time_unit' => DeliveryTimeUnit::class,
        'base_number_of_revisions' => 'integer',
        'base_revision_window' => 'integer',
        'milestone_available' => 'boolean',
        'is_active' => 'boolean',
        'status' => ServiceOfferStatus::class,
        'sort_order' => 'integer',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function orders(): MorphMany
    {
        return $this->morphMany(ServiceOfferOrder::class, 'orderable');
    }

    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function media()
    {
        return $this->hasMany(ServiceOfferMedia::class)->orderBy('sort_order');
    }

    public function coverMedia()
    {
        return $this->hasOne(ServiceOfferMedia::class)
            ->where('is_cover', true);
    }
    public function plans()
    {
        return $this->hasMany(ServiceOfferPlan::class)->orderBy('sort_order');
    }

    public function hasPricingPlans(): bool
    {
        if ($this->relationLoaded('plans')) {
            return $this->plans->isNotEmpty();
        }

        return $this->plans()->exists();
    }

    public function usesBasePricing(): bool
    {
        return ! $this->hasPricingPlans() && $this->base_price !== null;
    }

    public function effectivePriceFrom(): ?float
    {
        if ($this->hasPricingPlans()) {
            $plans = $this->relationLoaded('plans') ? $this->plans : $this->plans()->get();

            return (float) $plans->min('price');
        }

        return $this->base_price !== null ? (float) $this->base_price : null;
    }

    public function faqs()
    {
        return $this->hasMany(ServiceOfferFaq::class, 'service_offer_id')->orderBy('sort_order');
    }

    public function availableSlots()
    {
        return $this->hasMany(ServiceOfferAvailableSlot::class, 'service_offer_id')->orderBy('day')->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function providerProjects()
    {
        return $this->belongsToMany(
            ProviderProject::class,
            'provider_project_service_offer',
            'service_offer_id',
            'provider_project_id'
        )->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(ServiceOfferReview::class);
    }

    public function fastestPlan()
    {
        return $this->hasOne(ServiceOfferPlan::class)
            ->orderByRaw("
                CASE delivery_time_unit
                    WHEN 'hours' THEN 1
                    WHEN 'days' THEN 2
                END
             ")
            ->orderBy('delivery_time_value');
    }

    public function boostCampaigns()
    {
        return $this->hasMany(BoostCampaign::class);
    }

    /** Single row we keep extended while stacking paid boosts on the same listing. */
    public function activeBoostCampaign()
    {
        return $this->hasOne(BoostCampaign::class, 'service_offer_id')
            ->where('status', BoostCampaignStatus::Active)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }

    public function isEligibleForPaidBoostPurchase(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $value = $this->status instanceof ServiceOfferStatus
            ? $this->status->value
            : (string) $this->status;

        return $value === ServiceOfferStatus::Active->value;
    }

    public function isPublicSponsoredListing(): bool
    {
        return $this->relationLoaded('activeBoostCampaign')
            && $this->activeBoostCampaign !== null;
    }

    public function publicBoostScore(): float
    {
        if (! $this->isPublicSponsoredListing()) {
            return 0.0;
        }
        $campaign = $this->activeBoostCampaign;
        if (! $campaign->relationLoaded('plan')) {
            $campaign->loadMissing('plan');
        }

        return $campaign->plan?->searchRankMultiplier() ?? 1.25;
    }
}
