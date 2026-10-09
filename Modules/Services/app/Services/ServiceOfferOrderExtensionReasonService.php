<?php

namespace Modules\Services\Services;

use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderExtensionReasonRepositoryInterface;

class ServiceOfferOrderExtensionReasonService
{
    protected $serviceOfferOrderExtensionReasonRepository;

    public function __construct(ServiceOfferOrderExtensionReasonRepositoryInterface $serviceOfferOrderExtensionReasonRepository)
    {
        $this->serviceOfferOrderExtensionReasonRepository = $serviceOfferOrderExtensionReasonRepository;
    }

    public function list(?bool $isActive = null)
    {
        return $this->serviceOfferOrderExtensionReasonRepository->all($isActive);
    }

    public function getActiveReasons()
    {
        return $this->serviceOfferOrderExtensionReasonRepository->all(true);
    }

    public function findById($id)
    {
        return $this->serviceOfferOrderExtensionReasonRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->serviceOfferOrderExtensionReasonRepository->create([
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function update(array $data, $id)
    {
        $serviceOfferOrderExtensionReason = $this->serviceOfferOrderExtensionReasonRepository->findById($id);
        throw_if(!$serviceOfferOrderExtensionReason, ModelNotFoundException::class, "Record not found");
        return $this->serviceOfferOrderExtensionReasonRepository->update($data, $serviceOfferOrderExtensionReason);
    }


    public function toggle(int $id)
    {
        $reason = $this->serviceOfferOrderExtensionReasonRepository->findById($id);

        return $this->serviceOfferOrderExtensionReasonRepository->update([
            'is_active' => ! $reason->is_active,
        ], $reason);
    }


    public function delete($id)
    {
        $reason = $this->serviceOfferOrderExtensionReasonRepository->findById($id);
        if ($reason->extensions()->exists()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                422,
                'This reason is already used in extension requests and cannot be deleted. You can deactivate it instead.'
            );
        }
        return $this->serviceOfferOrderExtensionReasonRepository->delete($reason);
    }
}
