<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Enums\CustomRequestStatus;
use Modules\Services\Models\CustomRequest;

interface CustomRequestRepositoryInterface
{
    public function paginateForClient(int $clientId, ?CustomRequestStatus $status, int $perPage): LengthAwarePaginator;

    public function create(array $data): CustomRequest;

    public function referenceNumberExists(string $referenceNumber): bool;

    public function findWithDetailsForClient(int $clientId, int $id): CustomRequest;

    public function findWithQuotesForClient(int $clientId, int $id): CustomRequest;

    public function lockForUpdateForClientOrFail(int $clientId, int $id): CustomRequest;

    public function lockForUpdateByIdOrFail(int $id): CustomRequest;

    /**
     * @param  list<string>  $with
     */
    public function update(CustomRequest $customRequest, array $attributes, array $with = []): CustomRequest;

    /** @param  array<string, mixed>  $filters */
    public function paginateDiscoverForProvider(int $userId, int $serviceProviderId, array $filters, int $perPage): LengthAwarePaginator;

    public function findByIdWithDiscoverRelations(int $rfqId, int $serviceProviderId): CustomRequest;
}
