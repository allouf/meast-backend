<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Core\Models\User;
use Modules\Store\Models\Store;
use Illuminate\Database\Eloquent\Collection;

interface StoreRepositoryInterface
{
    public function all(array $params);
    public function getByUser(User $user);
    public function getDeletedByUser(User $user);
    public function getTrashed();
    public function findById(int $id);
    public function findByIdWithTrashed(int $id);
    public function create(array $data);
    public function update(array $data, Store $store);
    public function delete(Store $store);
    public function restore(Store $store);
    public function forceDelete(Store $store);
    public function updateStorePolicy(array $data);
    public function getPolicy(int $storeId, string $type);
    public function getByIds(array $ids);
    public function getCategoriesByStore(int $storeId);
}
