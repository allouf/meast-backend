<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Models\Quote;

interface CustomRequestQuoteRepositoryInterface
{
    public function findForUpdateOrFail(int $id): Quote;

    public function rfqHasAcceptedQuote(int $rfqId): bool;

    public function rfqHasProviderQuote(int $rfqId, int $serviceProviderId): bool;

    public function rejectPendingQuotesExcept(int $rfqId, int $exceptQuoteId): void;

    public function update(Quote $quote, array $attributes): Quote;

    public function createForCustomRequest(CustomRequest $customRequest, array $data): Quote;

    public function orderExistsForQuote(int $quoteId): bool;

    /**
     * @return array{total: int, awarded: int, under_review: int, rejected: int}
     */
    public function getDashboardStatsForProvider(int $serviceProviderId): array;

    public function paginateForProviderWithCustomRequest(int $serviceProviderId, int $perPage): LengthAwarePaginator;

    public function lockForProviderOrFail(int $quoteId, int $serviceProviderId): Quote;
}
