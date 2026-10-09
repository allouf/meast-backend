<?php

namespace Modules\Services\Services;

use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Services\Repositories\Interfaces\DisputeReasonRepositoryInterface;

class DisputeReasonService
{
    protected $disputeReasonRepository;

    public function __construct(DisputeReasonRepositoryInterface $disputeReasonRepository)
    {
        $this->disputeReasonRepository = $disputeReasonRepository;
    }

    public function list(?bool $isActive = null)
    {
        return $this->disputeReasonRepository->all($isActive);
    }

    public function findById($id)
    {
        return $this->disputeReasonRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->disputeReasonRepository->create($data);
    }

    public function update(array $data, $id)
    {
        $disputeReason = $this->disputeReasonRepository->findById($id);
        throw_if(!$disputeReason, ModelNotFoundException::class, "Record not found");
        return $this->disputeReasonRepository->update($data, $disputeReason);
    }


    public function toggle(int $id)
    {
        $reason = $this->disputeReasonRepository->findById($id);

        return $this->disputeReasonRepository->update([
            'is_active' => ! $reason->is_active,
        ], $reason);
    }
    public function delete($id)
    {
        $disputeReason = $this->disputeReasonRepository->findById($id);
        if ($disputeReason->disputes()->exists()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                422,
                'This reason is already used in disputes and cannot be deleted. You can deactivate it instead.'
            );
        }
        return $this->disputeReasonRepository->delete($disputeReason);
    }
}
