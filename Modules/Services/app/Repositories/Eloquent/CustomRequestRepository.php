<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Enums\CustomRequestStatus;
use Modules\Services\Filters\DiscoverCustomRequestFilter;
use Modules\Services\Enums\QuoteStatus;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Repositories\Interfaces\CustomRequestRepositoryInterface;

class CustomRequestRepository implements CustomRequestRepositoryInterface
{
    public function __construct(
        protected CustomRequest $model
    ) {}

    public function paginateForClient(int $clientId, ?CustomRequestStatus $status, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('client_id', $clientId)
            ->with(['category'])
            ->withCount('quotes')
            ->orderByDesc('created_at');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query->paginate($perPage);
    }

    public function create(array $data): CustomRequest
    {
        return $this->model->newQuery()->create($data);
    }

    public function referenceNumberExists(string $referenceNumber): bool
    {
        return $this->model->newQuery()->where('reference_number', $referenceNumber)->exists();
    }

    public function findWithDetailsForClient(int $clientId, int $id): CustomRequest
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->where('client_id', $clientId)
            ->with([
                'category',
                'attachments',
                'quotes' => function ($q) {
                    $q->orderByDesc('created_at')
                        ->with(['provider.user:id,name,photo,country_code']);
                },
            ])
            ->firstOrFail();
    }

    public function findWithQuotesForClient(int $clientId, int $id): CustomRequest
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->where('client_id', $clientId)
            ->with([
                'category',
                'quotes' => function ($q) {
                    $q->orderByDesc('created_at')
                        ->with(['provider.user:id,name,photo,country_code']);
                },
            ])
            ->firstOrFail();
    }

    public function lockForUpdateForClientOrFail(int $clientId, int $id): CustomRequest
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->where('client_id', $clientId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function lockForUpdateByIdOrFail(int $id): CustomRequest
    {
        return $this->model->newQuery()
            ->whereKey($id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function update(CustomRequest $customRequest, array $attributes, array $with = []): CustomRequest
    {
        $customRequest->update($attributes);

        return $with !== [] ? $customRequest->fresh($with) : $customRequest->fresh();
    }

    public function paginateDiscoverForProvider(int $userId, int $serviceProviderId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('status', CustomRequestStatus::Open)
            ->where('client_id', '!=', $userId)
            ->with(['category'])
            ->withCount('quotes')
            ->withExists([
                'quotes as has_applied' => function ($q) use ($serviceProviderId) {
                    $q->where('provider_id', $serviceProviderId)
                        ->where('status', '!=', QuoteStatus::Canceled->value);
                },
            ])
            ->orderBy('deadline_date');

        (new DiscoverCustomRequestFilter($filters))->apply($query);

        return $query->paginate($perPage);
    }

    public function findByIdWithDiscoverRelations(int $rfqId, int $serviceProviderId): CustomRequest
    {
        return $this->model->newQuery()
            ->whereKey($rfqId)
            ->with([
                'category',
                'attachments',
                'quotes' => function ($q) use ($serviceProviderId) {
                    $q->where('provider_id', $serviceProviderId)->orderByDesc('created_at');
                },
            ])
            ->firstOrFail();
    }
}
