<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Enums\MilestoneStatus;

class Milestone extends Model
{
    protected $fillable = [
        'service_provider_id',
        'service_offer_order_id',
        'title',
        'description',
        'delivery_model',
        'request_modification_days',
        'duration_days',
        'total_budget',
        'status',
        'activation_status',
        'sort_order',
    ];

    protected $casts = [
        'delivery_model' => DeliveryModel::class,
        'request_modification_days' => 'integer',
        'duration_days' => 'integer',
        'total_budget' => 'decimal:2',
        'status' => MilestoneStatus::class,
        'activation_status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function serviceOfferOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOfferOrder::class, 'service_offer_order_id');
    }

    /** Orders placed against this package (via service_offer_orders.orderable). */
    public function serviceOfferOrders(): MorphMany
    {
        return $this->morphMany(ServiceOfferOrder::class, 'orderable');
    }

    public function milestoneServices(): HasMany
    {
        return $this->hasMany(MilestoneService::class, 'milestone_id')->orderBy('sort_order');
    }

    public function recalculateAggregates(): void
    {
        $services = $this->milestoneServices();

        $this->forceFill([
            'total_budget' => (string) $services->sum('budget'),
            'duration_days' => (int) $services->sum('duration_days'),
        ])->saveQuietly();
    }
}
