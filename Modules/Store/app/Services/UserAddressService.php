<?php

namespace Modules\Store\Services;

use Illuminate\Support\Facades\DB;
use Modules\Store\Models\UserAddress;
use Modules\Store\Repositories\Interfaces\UserAddressRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserAddressService
{
    public function __construct(
        protected UserAddressRepositoryInterface $userAddressRepository
    ) {}

    public function addAddress(int $userId, array $data): UserAddress
    {
        return DB::transaction(function () use ($userId, $data) {
            if (isset($data['is_default']) && $data['is_default']) {
                $this->userAddressRepository->unsetDefaultAddresses($userId);
            }
            return $this->userAddressRepository->create([
                'user_id' => $userId,
                'title' => $data['title'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'country' => $data['country'],
                'country_code' => $data['country_code'],
                'postal_code' => $data['postal_code'] ?? null,
                'region' => $data['region'] ?? null,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'is_default' => $data['is_default'] ?? false,
            ]);
        });
    }

    public function updateAddress(int $userId, int $addressId, array $data): UserAddress
    {
        return DB::transaction(function () use ($userId, $addressId, $data) {
            $address = $this->userAddressRepository->findById($addressId);
            if (!$address) {
                throw new NotFoundHttpException('Address not found.');
            }
            if ($address->user_id !== $userId) {
                throw new HttpException(403, 'Address does not belong to you.');
            }
            if (isset($data['is_default']) && $data['is_default'] === true) {
                $this->userAddressRepository->unsetDefaultAddresses($userId);
            } elseif (!isset($data['is_default'])) {
                unset($data['is_default']);
            }
            return $this->userAddressRepository->update($data, $address);
        });
    }

    public function removeAddress(int $userId, int $addressId): bool
    {
        return DB::transaction(function () use ($userId, $addressId) {
            $address = $this->userAddressRepository->findById($addressId);
            if (!$address) {
                throw new NotFoundHttpException('Address not found.');
            }
            if ($address->user_id !== $userId) {
                throw new HttpException(403, 'Address does not belong to you.');
            }
            return $this->userAddressRepository->delete($address);
        });
    }

    public function getMyAddresses(int $userId)
    {
        return $this->userAddressRepository->findByUserId($userId);
    }
}
