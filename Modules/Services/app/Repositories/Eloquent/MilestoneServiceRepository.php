<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\Milestone;
use Modules\Services\Models\MilestoneService;
use Modules\Services\Repositories\Interfaces\MilestoneServiceRepositoryInterface;

class MilestoneServiceRepository implements MilestoneServiceRepositoryInterface
{
    public function __construct(
        protected MilestoneService $model
    ) {}

    public function create(Milestone $milestone, array $data): MilestoneService
    {
        return $milestone->milestoneServices()->create($data);
    }

    public function update(MilestoneService $line, array $data): MilestoneService
    {
        $line->update($data);

        return $line->fresh(['serviceCategory']);
    }

    public function delete(MilestoneService $line): void
    {
        $line->delete();
    }

    public function syncForMilestone(Milestone $milestone, array $items): void
    {
        $milestone->milestoneServices()->delete();

        foreach (array_values($items) as $index => $item) {
            $milestone->milestoneServices()->create([
                'service_category_id' => $item['service_category_id'],
                'service_name' => $item['service_name'],
                'description' => $item['description'] ?? null,
                'duration_days' => $item['duration_days'],
                'budget' => $item['budget'],
                'revision_window_days' => $item['revision_window_days'] ?? 0,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);
        }
    }

    public function findById(int $id, ?int $serviceProviderId = null): ?MilestoneService
    {
        $query = $this->model->newQuery()->with(['serviceCategory', 'milestone']);

        if ($serviceProviderId !== null) {
            $query->whereHas('milestone', fn ($q) => $q->where('service_provider_id', $serviceProviderId));
        }

        return $query->find($id);
    }
}
