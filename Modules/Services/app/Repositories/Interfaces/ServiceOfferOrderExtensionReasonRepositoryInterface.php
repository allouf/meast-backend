<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\ServiceOfferOrderExtensionReason;

interface ServiceOfferOrderExtensionReasonRepositoryInterface
{
    public function all(?bool $isActive = null);

    public function findById($id);

    public function create(array $data);

    public function update(array $data, ServiceOfferOrderExtensionReason $serviceOfferOrderExtensionReason);

    public function delete(ServiceOfferOrderExtensionReason $serviceOfferOrderExtensionReason);
}
