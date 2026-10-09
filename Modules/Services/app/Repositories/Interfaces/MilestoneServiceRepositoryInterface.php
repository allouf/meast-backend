<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\Milestone;
use Modules\Services\Models\MilestoneService;

interface MilestoneServiceRepositoryInterface
{
    public function create(Milestone $milestone, array $data): MilestoneService;

    public function update(MilestoneService $line, array $data): MilestoneService;

    public function delete(MilestoneService $line): void;

    public function syncForMilestone(Milestone $milestone, array $items): void;

    public function findById(int $id, ?int $serviceProviderId = null): ?MilestoneService;
}
