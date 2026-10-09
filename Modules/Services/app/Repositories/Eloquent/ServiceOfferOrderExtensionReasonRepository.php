<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\ServiceOfferOrderExtensionReason;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderExtensionReasonRepositoryInterface;

class ServiceOfferOrderExtensionReasonRepository implements ServiceOfferOrderExtensionReasonRepositoryInterface
{
    protected $model;

    public function __construct(ServiceOfferOrderExtensionReason $model)
    {
        $this->model = $model;
    }

    public function all(?bool $isActive = null)
    {
        return $this->model->newQuery() ->withCount('extensions')->when(! is_null($isActive), fn ($q) => $q->where('is_active', $isActive))
        ->latest()
        ->get();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, ServiceOfferOrderExtensionReason $serviceOfferOrderExtensionReason)
    {
        $serviceOfferOrderExtensionReason->update($data);
        return $serviceOfferOrderExtensionReason;
    }

    public function delete(ServiceOfferOrderExtensionReason $serviceOfferOrderExtensionReason)
    {
        return $serviceOfferOrderExtensionReason->delete();
    }
}
