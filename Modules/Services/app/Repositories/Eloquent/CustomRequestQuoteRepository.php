<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Enums\QuoteStatus;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Models\Quote;
use Modules\Services\Repositories\Interfaces\CustomRequestQuoteRepositoryInterface;

class CustomRequestQuoteRepository implements CustomRequestQuoteRepositoryInterface
{
    public function __construct(
        protected Quote $model
    ) {}

    public function findForUpdateOrFail(int $id): Quote
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function rfqHasAcceptedQuote(int $rfqId): bool
    {
        return $this->model->newQuery()
            ->where('rfq_id', $rfqId)
            ->where('status', QuoteStatus::Accepted)
            ->exists();
    }

    public function rfqHasProviderQuote(int $rfqId, int $serviceProviderId): bool
    {
        return $this->model->newQuery()
            ->where('rfq_id', $rfqId)
            ->where('provider_id', $serviceProviderId)
            ->where('status', '!=', QuoteStatus::Canceled->value)
            ->exists();
    }

    public function rejectPendingQuotesExcept(int $rfqId, int $exceptQuoteId): void
    {
        $this->model->newQuery()
            ->where('rfq_id', $rfqId)
            ->where('id', '!=', $exceptQuoteId)
            ->where('status', QuoteStatus::Pending)
            ->update(['status' => QuoteStatus::Rejected]);
    }

    public function update(Quote $quote, array $attributes): Quote
    {
        $quote->update($attributes);

        return $quote->fresh();
    }

    public function createForCustomRequest(CustomRequest $customRequest, array $data): Quote
    {
        return $customRequest->quotes()->create($data);
    }

    public function orderExistsForQuote(int $quoteId): bool
    {
        return $this->model->newQuery()
            ->whereKey($quoteId)
            ->whereHas('order')
            ->exists();
    }

    public function getDashboardStatsForProvider(int $serviceProviderId): array
    {
        $forProvider = fn () => $this->model->newQuery()->where('provider_id', $serviceProviderId);

        return [
            'total' => $forProvider()->count(),
            'awarded' => $forProvider()->where('status', QuoteStatus::Accepted)->count(),
            'under_review' => $forProvider()->where('status', QuoteStatus::Pending)->count(),
            'rejected' => $forProvider()->where('status', QuoteStatus::Rejected)->count(),
        ];
    }

    public function paginateForProviderWithCustomRequest(int $serviceProviderId, int $perPage): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('provider_id', $serviceProviderId)
            ->with([
                'customRequest' => function ($q) {
                    $q->with('category');
                },
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function lockForProviderOrFail(int $quoteId, int $serviceProviderId): Quote
    {
        return $this->model->newQuery()
            ->whereKey($quoteId)
            ->where('provider_id', $serviceProviderId)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
