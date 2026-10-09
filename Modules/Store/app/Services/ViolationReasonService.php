<?php

namespace Modules\Store\Services;

use Modules\Store\Models\ViolationReason;
use Modules\Store\Repositories\Interfaces\ViolationReasonRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ViolationReasonService
{
    protected $violationReasonRepository;

    public function __construct(ViolationReasonRepositoryInterface $violationReasonRepository)
    {
        $this->violationReasonRepository = $violationReasonRepository;
    }

    public function getAllReasons(array $filters = []): Collection
    {
        return $this->violationReasonRepository->all($filters);
    }

    public function getActiveReasons(): Collection
    {
        return $this->violationReasonRepository->getActive();
    }

    public function getReasonById(int $id)
    {
        return $this->violationReasonRepository->findById($id);
    }

    public function createReason(array $data): ViolationReason
    {
        return $this->violationReasonRepository->create($data);
    }

    /**
     * Update an existing reason
     */
    public function updateReason(int $id, array $data): ViolationReason
    {
        return $this->violationReasonRepository->update($id, $data);
    }

    /**
     * Delete a reason
     */
    public function deleteReason(int $id): bool
    {
        return $this->violationReasonRepository->delete($id);
    }
}
