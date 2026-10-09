<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\ServiceOfferOrderRejectionReason;

interface ServiceOfferOrderRejectionReasonRepositoryInterface
{
    public function all();

    public function getActive();
    
    public function findById($id);

    public function create(array $data);

    public function update(ServiceOfferOrderRejectionReason $serviceOfferOrderRejectionReason ,array $data);

    public function delete(ServiceOfferOrderRejectionReason $serviceOfferOrderRejectionReason);
}
