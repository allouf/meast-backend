<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\ServiceProvider;

interface ServiceProviderRepositoryInterface
{
    /** @param array<string> $relations */
    public function getByUserId(int $userId, array $relations = []): ?ServiceProvider;

    /** @return LengthAwarePaginator<ServiceProvider> */
    public function getPaginated(array $filters = []): LengthAwarePaginator;

    /** @return LengthAwarePaginator<ServiceProvider> */
    public function getVerificationRequestsPaginated(array $filters = []): LengthAwarePaginator;

    /** @param array<string> $relations */
    public function findById(int $id, array $relations = []): ?ServiceProvider;

    public function create(array $data): ServiceProvider;

    /** @param array<string> $withRelations */
    public function update(ServiceProvider $profile, array $data, array $withRelations = []): ServiceProvider;

    /** @param array<int> $languageIds */
    public function syncLanguages(ServiceProvider $profile, array $languageIds): void;

    public function isCustomerBlocked(int $providerId, int $buyerId): bool;

}
