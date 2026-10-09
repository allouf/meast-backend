<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\DisputeReason;
use Modules\Services\Repositories\Interfaces\DisputeReasonRepositoryInterface;

class DisputeReasonRepository implements DisputeReasonRepositoryInterface
{
    protected $model;

    public function __construct(DisputeReason $model)
    {
        $this->model = $model;
    }

    public function all(?bool $isActive = null)
    {
        return $this->model->newQuery()
        ->withCount('disputes')
        ->when(!is_null($isActive), fn ($q) => $q->where('is_active', $isActive))
        ->latest()
        ->get();
    }

    public function findById($id)
    {
        return $this->model->withCount('disputes')
        ->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, DisputeReason $disputeReason)
    {
        $disputeReason->update($data);
        return $disputeReason;
    }

    public function delete(DisputeReason $disputeReason)
    {
        return $disputeReason->delete();
    }
}
