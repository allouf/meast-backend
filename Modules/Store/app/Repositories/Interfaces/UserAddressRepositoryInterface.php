<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\UserAddress;

interface UserAddressRepositoryInterface
{
    public function findById(int $id): ?UserAddress;
    public function findByUserId(int $userId);
    public function create(array $data): UserAddress;
    public function update(array $data, UserAddress $address): UserAddress;
    public function delete(UserAddress $address): bool;
    public function setDefaultAddress(int $userId, int $addressId): bool;
    public function unsetDefaultAddresses(int $userId): bool;
}

