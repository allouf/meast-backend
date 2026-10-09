<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Services\Enums\BoostCampaignStatus;
use Modules\Services\Models\BoostCampaign;
use Modules\Services\Repositories\Interfaces\BoostCampaignRepositoryInterface;

class BoostCampaignRepository implements BoostCampaignRepositoryInterface
{
    protected $model;

    public function __construct(BoostCampaign $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model::with(['offer', 'provider', 'plan'])
        ->latest()
        ->get();
    }

    public function findById($id)
    {
        return $this->model::with(['offer', 'provider', 'plan'])->findOrFail($id);
    }

    public function findByIdWithAdminRelations(int $id): BoostCampaign
    {
        return $this->model::query()
            ->with([
                'offer.serviceCategory',
                'provider.user',
                'plan',
                'logs' => fn ($q) => $q->orderByDesc('id')->limit(30),
            ])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, BoostCampaign $boostCampaign)
    {
        $boostCampaign->update($data);
        return $boostCampaign;
    }

    public function delete(BoostCampaign $boostCampaign)
    {
        return $boostCampaign->delete();
    }

    public function paginatedForProvider(int $providerId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with(['offer', 'plan'])
            ->where('provider_id', $providerId)
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query->paginate($perPage > 0 ? $perPage : 15, ['*'], 'page', $page);
    }

    public function paginatedForAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with([
                'offer.serviceCategory',
                'provider.user',
                'plan',
                'logs' => fn ($q) => $q->latest('id')->limit(20),
            ])
            ->latest('boost_campaigns.id');

        if (! empty($filters['status'])) {
            $query->where('boost_campaigns.status', $filters['status']);
        }

        if (! empty($filters['statuses']) && is_array($filters['statuses'])) {
            $query->whereIn('boost_campaigns.status', $filters['statuses']);
        }

        if (! empty($filters['active_window_only'])) {
            $now = now();
            $query->where('boost_campaigns.status', BoostCampaignStatus::Active)
                ->where('boost_campaigns.start_at', '<=', $now)
                ->where('boost_campaigns.end_at', '>=', $now);
        }

        if (! empty($filters['provider_id'])) {
            $query->where('boost_campaigns.provider_id', (int) $filters['provider_id']);
        }

        if (! empty($filters['service_offer_id'])) {
            $query->where('boost_campaigns.service_offer_id', (int) $filters['service_offer_id']);
        }

        if (! empty($filters['search'])) {
            $s = trim((string) $filters['search']);
            $query->whereHas('offer', fn ($q) => $q->where('title', 'like', '%' . $s . '%'));
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query->paginate($perPage > 0 ? $perPage : 15, ['*'], 'page', $page);
    }

    public function lockActiveWindowForOfferAndProvider(int $serviceOfferId, int $providerId): ?BoostCampaign
    {
        $now = now();

        return $this->model->newQuery()
            ->where('service_offer_id', $serviceOfferId)
            ->where('provider_id', $providerId)
            ->where('status', BoostCampaignStatus::Active)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->orderByDesc('end_at')
            ->lockForUpdate()
            ->first();
    }

    public function lockActiveCampaignsForServiceOffer(int $serviceOfferId): Collection
    {
        $now = now();

        return $this->model->newQuery()
            ->with(['provider'])
            ->where('service_offer_id', $serviceOfferId)
            ->where('status', BoostCampaignStatus::Active)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->lockForUpdate()
            ->get();
    }
}
