<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Services\Models\ProviderProject;
use Modules\Services\Repositories\Interfaces\ProviderProjectRepositoryInterface;

class ProviderProjectRepository implements ProviderProjectRepositoryInterface
{
    private const DEFAULT_RELATIONS = ['serviceOffers'];

    public function __construct(
        protected ProviderProject $model
    ) {}

    /** @inheritdoc */
    public function getByServiceProviderId(int $serviceProviderId, array $filters = [], array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->where('service_provider_id', $serviceProviderId)
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        $rels = $relations !== [] ? $relations : self::DEFAULT_RELATIONS;
        $query->with($rels);

        $categoryId = $filters['category_id'] ?? null;
        $dateRange = $filters['date_range'] ?? null;
        $search = $filters['search'] ?? null;

        $query->when($categoryId, function ($q) use ($categoryId) {
            $q->whereHas('serviceOffers', function ($offerQuery) use ($categoryId) {
                $offerQuery->where('service_category_id', $categoryId);
            });
        });

        $query->when($dateRange, function ($q) use ($dateRange) {

            if (preg_match('/^last_(\d+)_days$/', $dateRange, $matches)) {

                $days = (int) $matches[1];

                if ($days > 0) {
                    $q->where(
                        'provider_projects.created_at',
                        '>=',
                        now()->subDays($days)
                    );
                }
            }

            if ($dateRange === 'this_year') {
                $q->where(
                    'provider_projects.created_at',
                    '>=',
                    now()->startOfYear()
                );
            }
        });

        $query->when($search, function ($q) use ($search) {
            $q->where(function ($inner) use ($search) {
                $inner->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        });

        $perPage = (int) ($filters['per_page'] ?? 12);
        $perPage = $perPage > 0 ? min($perPage, 50) : 12;

        $page = (int) ($filters['page'] ?? 1);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /** @inheritdoc */
    public function getPaginatedByServiceProviderId(int $serviceProviderId, array $filters = [], array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->where('service_provider_id', $serviceProviderId)
            ->orderBy('sort_order')
            ->orderByDesc('created_at');

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        $rels = $relations !== [] ? $relations : self::DEFAULT_RELATIONS;
        $query->with($rels);

        $perPage = (int) ($filters['per_page'] ?? 12);
        $perPage = $perPage > 0 ? min($perPage, 50) : 12;

        return $query->paginate($perPage);
    }

    /** @inheritdoc */
    public function findById(int $id, ?int $serviceProviderId = null, array $relations = []): ?ProviderProject
    {
        $query = $this->model->newQuery();
        if ($serviceProviderId !== null) {
            $query->where('service_provider_id', $serviceProviderId);
        }
        $rels = $relations !== [] ? $relations : self::DEFAULT_RELATIONS;
        $query->with($rels);

        return $query->find($id);
    }

    /** @inheritdoc */
    public function create(array $data): ProviderProject
    {
        return $this->model->create($data);
    }

    /** @inheritdoc */
    public function update(ProviderProject $project, array $data): ProviderProject
    {
        $project->update($data);
        return $project->fresh();
    }

    /** @inheritdoc */
    public function delete(ProviderProject $project): void
    {
        $project->delete();
    }

    /** @inheritdoc */
    public function syncServiceOffers(ProviderProject $project, array $serviceOfferIds): void
    {
        $project->serviceOffers()->sync($serviceOfferIds);
    }
}
