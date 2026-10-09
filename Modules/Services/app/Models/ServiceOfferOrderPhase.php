<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Services\Enums\MilestoneOrderPhaseStatus;
use Modules\Wallet\Enums\EscrowStatus;

class ServiceOfferOrderPhase extends Model
{
    protected $fillable = [
        'service_offer_order_id',
        'milestone_service_id',
        'service_name',
        'description',
        'service_category_id',
        'duration_days',
        'budget',
        'revision_window_days',
        'sort_order',
        'status',
        'started_at',
        'deadline',
        'delivered_at',
        'completed_at',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'budget' => 'decimal:2',
        'revision_window_days' => 'integer',
        'sort_order' => 'integer',
        'status' => MilestoneOrderPhaseStatus::class,
        'started_at' => 'datetime',
        'deadline' => 'datetime',
        'delivered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ServiceOfferOrder::class, 'service_offer_order_id');
    }

    public function milestoneService(): BelongsTo
    {
        return $this->belongsTo(MilestoneService::class, 'milestone_service_id');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function escrows()
    {
        return $this->morphMany(\Modules\Wallet\Models\Escrow::class, 'escrowable');
    }

    public function latestEscrow()
    {
        return $this->morphOne(\Modules\Wallet\Models\Escrow::class, 'escrowable')
            ->latestOfMany();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderDelivery::class, 'service_offer_order_phase_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ServiceOfferOrderRevision::class, 'service_offer_order_phase_id');
    }

    public function isFunded(): bool
    {
        $escrow = $this->relationLoaded('latestEscrow')
            ? $this->latestEscrow
            : $this->latestEscrow()->first();

        return $escrow?->status === EscrowStatus::Paid;
    }

    public function paymentStatus(): string
    {
        $escrow = $this->relationLoaded('latestEscrow')
            ? $this->latestEscrow
            : $this->latestEscrow()->first();

        return match ($escrow?->status) {
            EscrowStatus::Paid => 'in_escrow',
            EscrowStatus::Released => 'paid',
            EscrowStatus::Pending => 'pending',
            EscrowStatus::Failed => 'failed',
            EscrowStatus::Refunded => 'refunded',
            default => 'unpaid',
        };
    }

    public function allowedRevisions(): int
    {
        return $this->revision_window_days > 0 ? 1 : 0;
    }

    public function revisionsUsed(): int
    {
        return $this->revisions()
            ->where('status', '!=', 'REJECTED')
            ->count();
    }
}
