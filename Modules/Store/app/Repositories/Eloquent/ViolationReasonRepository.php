<?php

namespace Modules\Store\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Store\Models\ViolationReason;
use Modules\Store\Repositories\Interfaces\ViolationReasonRepositoryInterface;

class ViolationReasonRepository implements ViolationReasonRepositoryInterface
{
    protected $model;

    public function __construct(ViolationReason $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = []): Collection
    {
        $query = $this->model->query();

        // Filter by active status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Search by title
        if (!empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        // Apply ordering
        $query->ordered();

        return $query->get();
    }

    public function findById(int $id): ViolationReason
    {
        return $this->model->findOrFail($id);
    }

    public function getActive(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function create(array $data): ViolationReason
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ViolationReason
    {
        $reason = $this->findById($id);
        $reason->update($data);
        return $reason;
    }

    public function delete(int $id): bool
    {
        $reason = $this->findById($id);
        return $reason->delete();
    }
}
