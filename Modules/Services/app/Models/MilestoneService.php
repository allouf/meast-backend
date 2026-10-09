<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Services\Enums\MilestoneServiceStatus;

class MilestoneService extends Model
{
    protected $fillable = [
        'milestone_id',
        'service_category_id',
        'service_name',
        'description',
        'duration_days',
        'budget',
        'revision_window_days',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'budget' => 'decimal:2',
        'revision_window_days' => 'integer',
        'sort_order' => 'integer',
        'status' => MilestoneServiceStatus::class,
    ];

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class, 'milestone_id');
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }
}
