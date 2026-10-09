<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\ServiceOffer;

interface ServiceOfferRepositoryInterface
{
    /** @return LengthAwarePaginator<ServiceOffer> */
    public function getByServiceProviderId(int $serviceProviderId, array $filters = []): LengthAwarePaginator;

    /** @param array<string> $relations */
    public function findById(int $id, ?int $serviceProviderId = null, array $relations = []): ?ServiceOffer;

    public function create(array $data): ServiceOffer;

    public function update(ServiceOffer $offer, array $data): ServiceOffer;

    public function delete(ServiceOffer $offer): void;

    /** @param array<int, array<string, mixed>> $items */
    public function syncPlans(ServiceOffer $offer, array $items): void;

    /** @param array<int, array<string, mixed>> $items */
    public function syncFaqs(ServiceOffer $offer, array $items): void;

    /** @param array<int, array{day: string, slots: array<int, array{start_time: string, end_time: string}>}> $items */
    public function syncAvailableSlots(ServiceOffer $offer, array $items): void;

    /** @param array<int> $ids
     * @return array<int>
     */
    public function getIdsByServiceProviderAndIds(int $serviceProviderId, array $ids): array;

    /**
     * Get counts per status for a provider's service offers.
     *
     * @return array{active: int, paused: int, pending: int, rejected: int, draft: int, total: int}
     */
    public function getCountsByServiceProviderId(int $serviceProviderId): array;
}
