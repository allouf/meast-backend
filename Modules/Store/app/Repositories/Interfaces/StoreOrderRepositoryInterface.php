<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\StoreOrder;

interface StoreOrderRepositoryInterface
{
    public function findById(int $id): ?StoreOrder;
    public function create(array $data): StoreOrder;
    public function getStoreOrderWithItems(int $storeOrderId): ?StoreOrder;
    public function getStoreOrdersByStoreId(int $storeId, array $filters = []);
    public function getStoreOrdersByMasterOrderId(int $masterOrderId);
    public function update(int $storeOrderId, array $data): bool;
    public function getOrderStatisticsByStoreId(int $storeId, int $userId): array;
}

