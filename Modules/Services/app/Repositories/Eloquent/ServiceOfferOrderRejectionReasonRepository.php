<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRejectionReasonRepositoryInterface;

use Modules\Services\Models\ServiceOfferOrderRejectionReason;

class ServiceOfferOrderRejectionReasonRepository implements ServiceOfferOrderRejectionReasonRepositoryInterface
{
    protected $model;

    public function __construct(ServiceOfferOrderRejectionReason $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->orderBy('sort_order')
        ->latest()
        ->get();
    }

    public function getActive()
    {
        return $this->model->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name']);
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(ServiceOfferOrderRejectionReason $serviceOfferOrderRejectionReason, array $data)
    {
        $serviceOfferOrderRejectionReason->update($data);
        return $serviceOfferOrderRejectionReason;
    }

    public function delete(ServiceOfferOrderRejectionReason $serviceOfferOrderRejectionReason)
    {
        return $serviceOfferOrderRejectionReason->delete();
    }
}
