<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\BoostPlan;
use Modules\Services\Repositories\Interfaces\BoostPlanRepositoryInterface;

class BoostPlanRepository implements BoostPlanRepositoryInterface
{
    public function __construct(
        protected BoostPlan $model
    ) {}

    public function all()
    {
        return $this->model::with('variants')->latest()->get();
    }

    public function findById($id): ?BoostPlan
    {
        return $this->model::with('variants')->find($id);
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('variants');

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $query->latest('id');
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, BoostPlan $boostPlan): BoostPlan
    {
        $boostPlan->update($data);

        return $boostPlan->fresh(['variants']);
    }

    public function toggleActive(BoostPlan $boostPlan, bool $status): BoostPlan
    {
        $boostPlan->update(['is_active' => $status]);

        return $boostPlan->fresh(['variants']);
    }

    public function delete(BoostPlan $boostPlan)
    {
        return $boostPlan->delete();
    }
}
