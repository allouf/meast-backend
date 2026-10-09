<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Services\Models\ProviderProject;

interface ProviderProjectRepositoryInterface
{
  /**
     * @param int $serviceProviderId
     * @param array $filters
     * @param array $relations
     * @return LengthAwarePaginator<ProviderProject>
     */
    public function getByServiceProviderId(int $serviceProviderId, array $filters = [], array $relations = []): LengthAwarePaginator;

    /**
     * Get paginated projects for provider with optional search.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<ProviderProject>
     */
    public function getPaginatedByServiceProviderId(int $serviceProviderId, array $filters = [], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /** @param array<string> $relations */
    public function findById(int $id, ?int $serviceProviderId = null, array $relations = []): ?ProviderProject;

    public function create(array $data): ProviderProject;

    public function update(ProviderProject $project, array $data): ProviderProject;

    public function delete(ProviderProject $project): void;

    /** @param array<int> $serviceOfferIds */
    public function syncServiceOffers(ProviderProject $project, array $serviceOfferIds): void;
}
