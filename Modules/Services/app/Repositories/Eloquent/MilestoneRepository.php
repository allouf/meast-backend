<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\Milestone;
use Modules\Services\Repositories\Interfaces\MilestoneRepositoryInterface;

class MilestoneRepository implements MilestoneRepositoryInterface
{
    private const DEFAULT_RELATIONS = [
        'milestoneServices.serviceCategory',
        'serviceOfferOrder',
    ];

    public function __construct(
        protected Milestone $model
    ) {}

    public function getPublicActiveTemplates(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('activation_status', true)
            ->with([
                'serviceProvider',
                'milestoneServices.serviceCategory',
            ])
            ->withCount('milestoneServices')
            ->whereHas('milestoneServices')
            ->whereHas('serviceProvider.user', fn($q) => $q->where('is_active', true));

        $categoryId = $filters['category_id'] ?? $filters['service_category_id'] ?? null;
        if ($categoryId) {
            $query->whereHas(
                'milestoneServices',
                fn($q) => $q->where('service_category_id', (int) $categoryId)
            );
        }

        if (! empty($filters['delivery_model'])) {
            $query->where('delivery_model', $filters['delivery_model']);
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (! empty($filters['min_price'])) {
            $query->where('total_budget', '>=', (float) $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('total_budget', '<=', (float) $filters['max_price']);
        }

        $sort = $filters['sort'] ?? 'newest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'lowest_price' => $query->orderBy('total_budget')->orderBy('id'),
            'highest_price' => $query->orderByDesc('total_budget')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15);
    }

    public function getByServiceProviderId(int $serviceProviderId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->where('service_provider_id', $serviceProviderId)
            ->withCount('milestoneServices')
            ->with(self::DEFAULT_RELATIONS);

        if (isset($filters['activation_status'])) {
            $query->where('activation_status', filter_var($filters['activation_status'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (! empty($filters['delivery_model'])) {
            $query->where('delivery_model', $filters['delivery_model']);
        }

        $query->orderBy('sort_order')->orderByDesc('created_at');

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15);
    }

    public function findById(int $id, ?int $serviceProviderId = null, array $relations = []): ?Milestone
    {
        $query = $this->model->newQuery();

        if ($serviceProviderId !== null) {
            $query->where('service_provider_id', $serviceProviderId);
        }

        $rels = $relations !== [] ? $relations : self::DEFAULT_RELATIONS;

        return $query->with($rels)->find($id);
    }

    public function findTemplateById(int $id, ?int $serviceProviderId = null): ?Milestone
    {
        $query = $this->model->newQuery();

        if ($serviceProviderId !== null) {
            $query->where('service_provider_id', $serviceProviderId);
        }

        return $query->with(self::DEFAULT_RELATIONS)->find($id);
    }

    public function findPublicTemplateById(int $id): ?Milestone
    {
        return $this->model->newQuery()
            ->where('activation_status', true)
            ->whereHas('milestoneServices')
            ->whereHas('serviceProvider.user', fn($q) => $q->where('is_active', true))
            ->with(self::DEFAULT_RELATIONS)
            ->find($id);
    }

    public function findPublicActiveTemplateByServiceOfferId(int $serviceOfferId): ?Milestone
    {
        return $this->model->newQuery()
            ->whereHas('serviceOfferOrder', fn ($q) => $q->where('reference_service_offer_id', $serviceOfferId))
            ->where('activation_status', true)
            ->whereHas('milestoneServices')
            ->whereHas('serviceProvider.user', fn($q) => $q->where('is_active', true))
            ->with(self::DEFAULT_RELATIONS)
            ->orderByDesc('id')
            ->first();
    }

    public function create(array $data): Milestone
    {
        return $this->model->create($data);
    }

    public function update(Milestone $milestone, array $data): Milestone
    {
        $milestone->update($data);

        return $milestone->fresh(self::DEFAULT_RELATIONS);
    }

    public function delete(Milestone $milestone): void
    {
        $milestone->delete();
    }

    public function toggleActivation(Milestone $milestone): Milestone
    {
        $milestone->update([
            'activation_status' => ! $milestone->activation_status,
        ]);

        return $milestone->fresh(self::DEFAULT_RELATIONS);
    }
}
