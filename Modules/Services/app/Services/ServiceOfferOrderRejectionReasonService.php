<?php

namespace Modules\Services\Services;

use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRejectionReasonRepositoryInterface;


class ServiceOfferOrderRejectionReasonService
{
    protected $serviceOfferOrderRejectionReasonRepository;

    public function __construct(ServiceOfferOrderRejectionReasonRepositoryInterface $serviceOfferOrderRejectionReasonRepository)
    {
        $this->serviceOfferOrderRejectionReasonRepository = $serviceOfferOrderRejectionReasonRepository;
    }

    public function getAll()
    {
        return $this->serviceOfferOrderRejectionReasonRepository->all();
    }

    public function getActive()
    {
        return $this->serviceOfferOrderRejectionReasonRepository->getActive();
    }

    public function getById(int $id)
    {
        return $this->serviceOfferOrderRejectionReasonRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->serviceOfferOrderRejectionReasonRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $reason = $this->serviceOfferOrderRejectionReasonRepository->findById($id);

        return $this->serviceOfferOrderRejectionReasonRepository->update($reason, $data);
    }

    public function delete(int $id)
    {
        $reason = $this->serviceOfferOrderRejectionReasonRepository->findById($id);

        return $this->serviceOfferOrderRejectionReasonRepository->delete($reason);
    }
}
