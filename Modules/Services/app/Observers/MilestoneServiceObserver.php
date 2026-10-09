<?php

namespace Modules\Services\Observers;

use Modules\Services\Models\MilestoneService;

class MilestoneServiceObserver
{
    public function saved(MilestoneService $milestoneService): void
    {
        $milestoneService->milestone?->recalculateAggregates();
    }

    public function deleted(MilestoneService $milestoneService): void
    {
        $milestoneService->milestone?->recalculateAggregates();
    }
}
