<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\MasterOrder;

interface MasterOrderRepositoryInterface
{
    public function findByUserId(int $userId);
    public function findById(int $id): ?MasterOrder;
    public function create(array $data): MasterOrder;
    public function getMasterOrderWithStoreOrders(int $masterOrderId): ?MasterOrder;
    public function getMasterOrdersByUserId(int $userId);
    public function update(int $masterOrderId, array $data): bool;
    public function getAllMasterOrders(array $filters = []);
}

