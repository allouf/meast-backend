<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Enums\ServiceOfferStatus;
use Modules\Services\Filters\ServiceOfferFilter;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Repositories\Interfaces\ServiceOfferRepositoryInterface;

class ServiceOfferRepository implements ServiceOfferRepositoryInterface
{
    private const DEFAULT_RELATIONS = ['serviceCategory', 'plans', 'faqs', 'availableSlots', 'media','serviceProvider', 'coverMedia','fastestPlan','reviews.user'];

    public function __construct(
        protected ServiceOffer $model
    ) {}

    /** @inheritdoc */
    public function getByServiceProviderId(int $serviceProviderId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->where('service_provider_id', $serviceProviderId)
            ->with(self::DEFAULT_RELATIONS)->withAvg('reviews', 'rating');

        $query = (new ServiceOfferFilter($filters))->apply($query);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $page = (int) ($filters['page'] ?? 1);

        return $query->paginate($perPage > 0 ? $perPage : 15, ['*'], 'page', $page);
    }

    /** @inheritdoc */
    public function findById(int $id, ?int $serviceProviderId = null, array $relations = []): ?ServiceOffer
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
    public function create(array $data): ServiceOffer
    {
        return $this->model->create($data);
    }

    /** @inheritdoc */
    public function update(ServiceOffer $offer, array $data): ServiceOffer
    {
        $offer->update($data);
        return $offer->fresh();
    }

    /** @inheritdoc */
    public function delete(ServiceOffer $offer): void
    {
        $offer->delete();
    }

    /** @inheritdoc */
    public function syncPlans(ServiceOffer $offer, array $items): void
    {
        $offer->plans()->delete();
        foreach (array_values($items) as $i => $item) {
            $offer->plans()->create([
                'title' => $item['title'] ?? null,
                'price' => $item['price'],
                'currency' => $item['currency'],
                'delivery_time_value' => $item['delivery_time_value'] ?? null,
                'delivery_time_unit' => $item['delivery_time_unit'] ?? null,
                'revision_window' => $item['revision_window'] ?? null,
                'number_of_revisions' => $item['number_of_revisions'] ?? null,
                'revision_scope_rules' => $item['revision_scope_rules'] ?? null,
                'sort_order' => $i,
            ]);
        }
    }

    /** @inheritdoc */
    public function syncFaqs(ServiceOffer $offer, array $items): void
    {
        $offer->faqs()->delete();
        foreach (array_values($items) as $i => $item) {
            $offer->faqs()->create([
                'question' => $item['question'],
                'answer' => $item['answer'],
                'sort_order' => $i,
            ]);
        }
    }

    /** @inheritdoc */
    public function syncAvailableSlots(ServiceOffer $offer, array $items): void
    {
        $offer->availableSlots()->delete();
        $sortOrder = 0;
        foreach ($items as $dayGroup) {
            $day = $dayGroup['day'] ?? null;
            $slots = $dayGroup['slots'] ?? [];
            if (! $day || ! is_array($slots)) {
                continue;
            }
            foreach ($slots as $slot) {
                $offer->availableSlots()->create([
                    'day' => $day,
                    'start_time' => $slot['start_time'] ?? '',
                    'end_time' => $slot['end_time'] ?? '',
                    'sort_order' => $sortOrder++,
                ]);
            }
        }
    }

    /** @inheritdoc */
    public function getIdsByServiceProviderAndIds(int $serviceProviderId, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        return $this->model->where('service_provider_id', $serviceProviderId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();
    }

    /** @inheritdoc */
    public function getCountsByServiceProviderId(int $serviceProviderId): array
    {
        $counts = $this->model->where('service_provider_id', $serviceProviderId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $active = (int) ($counts[ServiceOfferStatus::Active->value] ?? 0);
        $paused = (int) ($counts[ServiceOfferStatus::Paused->value] ?? 0);
        $pending = (int) ($counts[ServiceOfferStatus::Pending->value] ?? 0);
        $rejected = (int) ($counts[ServiceOfferStatus::Rejected->value] ?? 0);
        $draft = (int) ($counts[ServiceOfferStatus::Draft->value] ?? 0);

        return [
            'active' => $active,
            'paused' => $paused,
            'pending' => $pending,
            'rejected' => $rejected,
            'draft' => $draft,
            'total' => $active + $paused + $pending + $rejected + $draft,
        ];
    }
}
