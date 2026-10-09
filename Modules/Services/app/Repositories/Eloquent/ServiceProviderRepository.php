<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Enums\ProviderVerificationStatus;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Repositories\Interfaces\ServiceProviderRepositoryInterface;

class ServiceProviderRepository implements ServiceProviderRepositoryInterface
{
    public function __construct(
        protected ServiceProvider $model
    ) {}

    /** @inheritdoc */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with(['user:id,name,email', 'languages'])->withCount('serviceOffers');

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('display_name', 'like', $term)
                    ->orWhere('region', 'like', $term)
                    ->orWhere('country_code', 'like', $term);
            });
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['verification_status'])) {
            $query->where('verification_status', $filters['verification_status']);
        }

        $query->orderByDesc('created_at');
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15);
    }

    /** @inheritdoc */
    public function getVerificationRequestsPaginated(array $filters = []): LengthAwarePaginator
    {
        $filters['verification_status'] = $filters['verification_status']
            ?? ProviderVerificationStatus::Pending->value;

        return $this->getPaginated($filters);
    }

    /** @inheritdoc */
    public function findById(int $id, array $relations = []): ?ServiceProvider
    {
        $query = $this->model->newQuery();
        if ($relations !== []) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    /** @inheritdoc */
    public function getByUserId(int $userId, array $relations = []): ?ServiceProvider
    {
        $query = $this->model->where('user_id', $userId);
        if ($relations !== []) {
            $query->with($relations);
        }
        return $query->first();
    }

    /** @inheritdoc */
    public function create(array $data): ServiceProvider
    {
        return $this->model->create($data);
    }

    /** @inheritdoc */
    public function update(ServiceProvider $profile, array $data, array $withRelations = []): ServiceProvider
    {
        $profile->update($data);
        return $profile->fresh($withRelations);
    }

    /** @inheritdoc */
    public function syncLanguages(ServiceProvider $profile, array $languageIds): void
    {
        $profile->languages()->sync($languageIds);
    }

    public function isCustomerBlocked(int $providerId, int $buyerId): bool
    {
        return \DB::table('service_provider_blocks')
            ->where('service_provider_id', $providerId)
            ->where('buyer_id', $buyerId)
            ->exists();
    }
}
