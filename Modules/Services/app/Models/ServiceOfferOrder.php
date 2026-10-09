<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Enums\ServiceOfferOrderStatus;

class ServiceOfferOrder extends Model
{
    use HasFactory;

    protected $table = 'service_offer_orders';

    protected $fillable = [
        'order_number',
        'buyer_id',
        'provider_id',
        'reference_service_offer_id',
        'orderable_type',
        'orderable_id',
        'title',
        'price',
        'currency',
        'delivery_time_minutes',
        'requirements',
        'milestone_snapshot',
        'status',
        'rejection_reason_id',
        'rejection_notes',
        'started_at',
        'deadline',
        'current_phase_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'status' => ServiceOfferOrderStatus::class,
        'requirements' => 'array',
        'milestone_snapshot' => 'array',
        'started_at' => 'datetime',
        'deadline' => 'datetime',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\Modules\Core\Models\User::class, 'buyer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function referenceServiceOffer(): BelongsTo
    {
        return $this->belongsTo(ServiceOffer::class, 'reference_service_offer_id');
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function escrows()
    {
        return $this->morphMany(\Modules\Wallet\Models\Escrow::class, 'escrowable');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderDelivery::class, 'service_offer_order_id');
    }

    public function latestEscrow()
    {
        return $this->morphOne(\Modules\Wallet\Models\Escrow::class, 'escrowable')
            ->latestOfMany();
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderRevision::class, 'service_offer_order_id');
    }

    public function rejectionReason(): BelongsTo
    {
        return $this->belongsTo(ServiceOfferOrderRejectionReason::class);
    }

    public function extensions(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderExtension::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderDispute::class, 'service_offer_order_id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderPhase::class, 'service_offer_order_id')
            ->orderBy('sort_order');
    }

    public function currentPhase(): BelongsTo
    {
        return $this->belongsTo(ServiceOfferOrderPhase::class, 'current_phase_id');
    }

    public function orderableType(): ?OrderableType
    {
        if ($this->isMilestoneOrder()) {
            return OrderableType::MilestoneTemplate;
        }

        return OrderableType::tryFrom((string) $this->orderable_type);
    }

    public function isServiceOfferOrder(): bool
    {
        return $this->orderable_type !== null
            && $this->orderable_id !== null
            && $this->orderableType() === OrderableType::ServiceOffer;
    }

    public function isMilestoneOrder(): bool
    {
        return ($this->requirements['order_type'] ?? null) === OrderableType::MilestoneTemplate->value
            || $this->current_phase_id !== null
            || (
                $this->orderable_type === OrderableType::MilestoneTemplate->value
                && $this->orderable_id !== null
            );
    }

    public function offer(): ?ServiceOffer
    {
        if ($this->isMilestoneOrder()) {
            if ($this->relationLoaded('referenceServiceOffer')) {
                return $this->referenceServiceOffer;
            }

            if ($this->reference_service_offer_id) {
                return ServiceOffer::query()->find((int) $this->reference_service_offer_id);
            }

            return null;
        }

        if (! $this->isServiceOfferOrder()) {
            return null;
        }

        $orderable = $this->relationLoaded('orderable')
            ? $this->orderable
            : $this->orderable()->first();

        return $orderable instanceof ServiceOffer ? $orderable : null;
    }

    public function selectedPlan(): ?ServiceOfferPlan
    {
        $planId = $this->requirements['plan_id'] ?? null;

        if (! $planId) {
            return null;
        }

        return ServiceOfferPlan::query()->find((int) $planId);
    }

    /** @deprecated Use selectedPlan() */
    public function plan(): ?ServiceOfferPlan
    {
        return $this->selectedPlan();
    }

    /** @deprecated Milestone is linked via orderable morph after provider creates the package. */
    public function milestoneTemplate(): ?Milestone
    {
        if (! $this->isMilestoneOrder()) {
            return null;
        }

        $orderable = $this->relationLoaded('orderable')
            ? $this->orderable
            : $this->orderable()->first();

        return $orderable instanceof Milestone ? $orderable : null;
    }

    public function effectivePrice(): float
    {
        if ($this->isMilestoneOrder()) {
            if ($this->relationLoaded('phases')) {
                return (float) $this->phases->sum('budget');
            }

            if ($this->current_phase_id !== null) {
                return (float) $this->phases()->sum('budget');
            }

            return (float) $this->price;
        }

        return (float) $this->price;
    }

    public function effectiveOrderableId(): ?int
    {
        if ($this->orderable_id !== null) {
            return (int) $this->orderable_id;
        }

        return null;
    }

    public function effectiveDeliveryMinutes(): int
    {
        if ($this->delivery_time_minutes !== null) {
            return (int) $this->delivery_time_minutes;
        }

        return 0;
    }

    public function allowedRevisions(): int
    {
        if ($this->isMilestoneOrder()) {
            $phase = $this->relationLoaded('currentPhase')
                ? $this->currentPhase
                : $this->currentPhase()->first();

            return $phase?->allowedRevisions() ?? 0;
        }

        $fromPlan = $this->selectedPlan()?->number_of_revisions;

        if ($fromPlan !== null) {
            return (int) $fromPlan;
        }

        return (int) ($this->offer()?->base_number_of_revisions ?? 0);
    }
}
