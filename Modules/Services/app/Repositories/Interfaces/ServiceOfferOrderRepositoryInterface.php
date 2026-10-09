<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\ServiceOfferOrder;

interface ServiceOfferOrderRepositoryInterface
{
    public function all();

    public function findById($id);

    public function create(array $data);

    public function update(array $data, ServiceOfferOrder $serviceOrder);

    public function delete(ServiceOfferOrder $serviceOrder);

    public function getLastOrderOfYear(string $year): ?ServiceOfferOrder;

    public function getOrdersByBuyer(int $buyerId, ?ServiceOfferOrderStatus $status = null);

    public function getOrdersByProvider(int $providerId, ?ServiceOfferOrderStatus $status = null);

    public function getOrdersByService(int $serviceOfferId, ?ServiceOfferOrderStatus $status = null);

    public function findNegotiationOrderForProvider(int $providerId, int $orderId): ?ServiceOfferOrder;
}
