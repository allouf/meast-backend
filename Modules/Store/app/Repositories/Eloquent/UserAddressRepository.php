<?php

namespace Modules\Store\Repositories\Eloquent;

use Modules\Store\Models\UserAddress;
use Modules\Store\Repositories\Interfaces\UserAddressRepositoryInterface;

class UserAddressRepository implements UserAddressRepositoryInterface
{
    public function __construct(
        protected UserAddress $model
    ) {}

    public function findById(int $id): ?UserAddress
    {
        return $this->model->find($id);
    }

    public function findByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function create(array $data): UserAddress
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserAddress $address): UserAddress
    {
        $address->update($data);
        return $address;
    }

    public function delete(UserAddress $address): bool
    {
        return $address->delete();
    }

    public function setDefaultAddress(int $userId, int $addressId): bool
    {
        // First unset all default addresses for this user
        $this->unsetDefaultAddresses($userId);

        // Set the new default address
        return $this->model->where('id', $addressId)
            ->where('user_id', $userId)
            ->update(['is_default' => true]);
    }

    public function unsetDefaultAddresses(int $userId): bool
    {
        return $this->model->where('user_id', $userId)
            ->update(['is_default' => false]);
    }
}

