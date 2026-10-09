<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Services\Library\ServiceOfferElasticsearch;
use Modules\Services\Models\ProviderProject;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Models\ServiceOfferAvailableSlot;
use Modules\Services\Models\ServiceOfferFaq;
use Modules\Services\Models\ServiceOfferPlan;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Enums\ServiceOfferStatus;
use Modules\Services\Repositories\Interfaces\ServiceOfferRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferService
{
    private const COVER_DIR = 'service-offers/media';

    public function __construct(
        protected ServiceOfferRepositoryInterface $serviceOfferRepository,
        protected ServiceOfferPublicSearchService $publicSearchService,
        protected CategoryVerificationService $categoryVerificationService,
    ) {}

    public function getByProvider(ServiceProvider $serviceProvider, array $filters = []): LengthAwarePaginator
    {
        if ($this->shouldUseElasticsearch($filters)) {
            return $this->searchWithElasticsearch($serviceProvider, $filters);
        }
        return $this->serviceOfferRepository->getByServiceProviderId($serviceProvider->id, $filters);
    }

    private function shouldUseElasticsearch(array $filters): bool
    {
        $hasSearch = ! empty($filters['search']);
        $hasFilters = ! empty($filters['category_id']) || ! empty($filters['service_category_id'])
            || ! empty($filters['delivery_model']) || ! empty($filters['service_date_from'])
            || ! empty($filters['service_date_to']) || ! empty($filters['service_type'])
            || ! empty($filters['type']) || ! empty($filters['status']);
        return $hasSearch || $hasFilters;
    }

    private function searchWithElasticsearch(ServiceProvider $serviceProvider, array $filters): LengthAwarePaginator
    {
        $es = new ServiceOfferElasticsearch();
        $result = $es->searchWithFilters($serviceProvider->id, $filters);
        $hits = $result['hits']['hits'] ?? [];
        $total = $result['hits']['total']['value'] ?? 0;
        $ids = collect($hits)->pluck('_id')->map(fn($id) => (int) $id)->toArray();
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);

        if (empty($ids)) {
            return new LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $offers = ServiceOffer::whereIn('id', $ids)
            ->with(['serviceCategory', 'plans', 'faqs', 'availableSlots'])
            ->get()
            ->sortBy(fn($o) => array_search($o->id, $ids))
            ->values();

        return new LengthAwarePaginator(
            $offers,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Get service offer counts per status for the provider (for dashboard stats).
     *
     * @return array{active: int, paused: int, pending: int, rejected: int, draft: int, total: int}
     */
    public function getStatsByProvider(ServiceProvider $serviceProvider): array
    {
        return $this->serviceOfferRepository->getCountsByServiceProviderId($serviceProvider->id);
    }

    public function getById(int $id, ?int $serviceProviderId = null): ?ServiceOffer
    {
        return $this->serviceOfferRepository->findById($id, $serviceProviderId);
    }

    /**
     * Public browse (US‑P3): boosted ranking with guardrails and snapshot ordering.
     *
     * @return array{paginator: LengthAwarePaginator<\Modules\Services\Models\ServiceOffer>, ranking_snapshot_at: string, ranking_pool_limited: bool}
     */
    public function getListForPublic(array $filters = []): array
    {
        return $this->publicSearchService->paginate($filters);
    }

    /** Single public offer by id (active only). */
    public function getPublicById(int $id): ?ServiceOffer
    {
        $offer = $this->serviceOfferRepository->findById($id, null);
        if (! $offer || ! $offer->is_active || $offer->status !== ServiceOfferStatus::Active) {
            return null;
        }
        $offer->loadMissing(['serviceProvider', 'activeBoostCampaign.plan']);
        return $offer;
    }

    /**
     * Get provider projects (portfolio) linked to a public service offer (paginated).
     * Returns null if service not found or not public; otherwise LengthAwarePaginator.
     *
     * @param array{page?: int, per_page?: int} $params
     * @return LengthAwarePaginator|null
     */
    public function getProjectsByServiceId(int $serviceId, array $params = []): ?LengthAwarePaginator
    {
        $offer = $this->getPublicById($serviceId);
        if (! $offer) {
            return null;
        }
        $perPage = (int) ($params['per_page'] ?? 15);
        $page = (int) ($params['page'] ?? 1);
        $perPage = $perPage > 0 ? $perPage : 15;

        $search = $params['search'] ?? null;
        $categoryId = $params['category_id'] ?? null;
        $dateRange = $params['date_range'] ?? null;

        $query = $offer->providerProjects()
            ->with([
                'serviceProvider',
                'serviceOffers.serviceCategory'
            ]);

        if ($categoryId) {
            $query->whereHas('serviceOffers', function ($q) use ($categoryId) {
                $q->where('service_category_id', $categoryId);
            });
        }

        if ($dateRange) {

            if (preg_match('/^last_(\d+)_days$/', $dateRange, $matches)) {

                $days = (int) $matches[1];

                if ($days > 0) {
                    $query->where(
                        'provider_projects.created_at',
                        '>=',
                        now()->subDays($days)
                    );
                }
            } elseif ($dateRange === 'this_year') {
                $query->where(
                    'provider_projects.created_at',
                    '>=',
                    now()->startOfYear()
                );
            }
        }


        if ($search) {
            $query->where(function ($q) use ($search) {

                $q->where('provider_projects.title', 'LIKE', "%{$search}%")
                    ->orWhere('provider_projects.description', 'LIKE', "%{$search}%");
            });
        }

        return $query
            ->orderBy('provider_projects.sort_order')
            ->orderByDesc('provider_projects.created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(ServiceProvider $serviceProvider, array $data, ?array $files = null): ServiceOffer
    {
        $willBeActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        $this->categoryVerificationService->assertCanPublishInCategory(
            $serviceProvider,
            (int) $data['service_category_id'],
            $willBeActive
        );

        $data['service_provider_id'] = $serviceProvider->id;

        $plans = $data['plans'] ?? [];
        $faqs = $data['faqs'] ?? [];
        $availableSlots = $data['available_slots'] ?? [];
        unset($data['plans'], $data['faqs'], $data['available_slots'], $data['media'], $data['cover']);

        $data = $this->normalizeBasePricingFields($data, $plans);
        $data['milestone_available'] = (bool) ($data['milestone_available'] ?? false);

        $offer = $this->serviceOfferRepository->create($data);
        $this->serviceOfferRepository->syncPlans($offer, $plans);
        $this->serviceOfferRepository->syncFaqs($offer, $faqs);
        $this->serviceOfferRepository->syncAvailableSlots($offer, $availableSlots);

        if (!empty($files)) {
            $this->storeMedia($offer, $files);
        }

        return $this->serviceOfferRepository->findById($offer->id, null, ['serviceCategory', 'plans', 'faqs', 'availableSlots', 'media']);
    }

    private function storeMedia(ServiceOffer $offer, array $files): void
    {

        if (!empty($files['cover'])) {

            $file = $files['cover'];

            $path = FileManager::upload(self::COVER_DIR, $files['cover']);
            $mime = $files['cover']->getMimeType();
            $type = str_contains($mime, 'video') ? 'video' : 'image';

            $offer->media()->create([
                'media_path' => $path,
                'media_type' => $type,
                'is_cover' => true,
                'sort_order' => 0,
            ]);
        }

        if (!empty($files['media'])) {

            foreach ($files['media'] as $index => $mediaItem) {

                if (empty($mediaItem['file'])) {
                    continue;
                }


                $file = $mediaItem['file'];

                $path = FileManager::upload(self::COVER_DIR, $file);

                $mime = $file->getMimeType();

                $type = str_contains($mime, 'video') ? 'video' : 'image';

                $offer->media()->create([
                    'media_path' => $path,
                    'media_type' => $type,
                    'is_cover' =>  false,
                    'sort_order' => $mediaItem['sort_order'] ?? $index,
                ]);
            }
        }
    }


    public function update(ServiceOffer $offer, array $data, ?array $mediaFiles = null): ServiceOffer
    {
        $categoryId = (int) ($data['service_category_id'] ?? $offer->service_category_id);
        $willBeActive = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : (bool) $offer->is_active;

        $this->categoryVerificationService->assertCanPublishInCategory(
            $offer->serviceProvider,
            $categoryId,
            $willBeActive
        );

        $plans = $data['plans'] ?? null;
        $faqs = $data['faqs'] ?? null;
        $availableSlots = $data['available_slots'] ?? null;

        $coverMediaId = $data['cover_media_id'] ?? null;


        unset(
            $data['plans'],
            $data['faqs'],
            $data['available_slots'],
            $data['media'],
            $data['delete_media_ids'],
            $data['cover_media_id']
        );

        if ($plans !== null) {
            $data = $this->normalizeBasePricingFields($data, $plans);
        }

        $offer = $this->serviceOfferRepository->update($offer, $data);
        if ($plans !== null) {
            $this->serviceOfferRepository->syncPlans($offer, $plans);
        }
        if ($faqs !== null) {
            $this->serviceOfferRepository->syncFaqs($offer, $faqs);
        }
        if ($availableSlots !== null) {
            $this->serviceOfferRepository->syncAvailableSlots($offer, $availableSlots);
        }

        if (!is_null($mediaFiles['media'])) {
            $this->syncMedia($offer, $mediaFiles['media']);
        }

        if (!empty($mediaFiles['cover'])) {
            $this->replaceCover($offer, $mediaFiles['cover']);
        }

        if ($coverMediaId) {
            $this->setCover($offer, $coverMediaId);
        }

        // $this->ensureCoverExists($offer);

        return $this->serviceOfferRepository->findById($offer->id, null, ['serviceCategory', 'plans', 'faqs', 'availableSlots', 'media']);
    }

    private function syncMedia(ServiceOffer $offer, array $mediaInput): void
    {
        $existingMedia = $offer->media()->get()->keyBy('id');

        $requestIds = collect($mediaInput)
            ->pluck('id')
            ->filter()
            ->values()
            ->toArray();

        $this->deleteMissingMedia($existingMedia, $requestIds);

        foreach ($mediaInput as $index => $item) {

            if (!empty($item['id']) && $existingMedia->has($item['id'])) {

                $existingMedia[$item['id']]->update([
                    'sort_order' => $item['sort_order'] ?? $index,
                ]);
            } elseif (!empty($item['file'])) {

                $this->createMedia($offer, $item, $index);
            }
        }
    }

    private function deleteMissingMedia($existingMedia, array $requestIds): void
    {
        $toDelete = $existingMedia->whereNotIn('id', $requestIds);

        foreach ($toDelete as $media) {
            FileManager::delete($media->media_path);
            $media->delete();
        }
    }


    private function createMedia(ServiceOffer $offer, array $item, int $index): void
    {
        $file = $item['file'];

        $path = FileManager::upload(self::COVER_DIR, $file);

        $mime = $file->getMimeType();
        $type = str_contains($mime, 'video') ? 'video' : 'image';

        $offer->media()->create([
            'media_path' => $path,
            'media_type' => $type,
            'is_cover' => false,
            'sort_order' => $item['sort_order'] ?? $index,
        ]);
    }

    private function replaceCover(ServiceOffer $offer, $file): void
    {
        $oldCover = $offer->media()->where('is_cover', true)->first();

        if ($oldCover) {
            FileManager::delete($oldCover->media_path);
            $oldCover->delete();
        }

        $path = FileManager::upload(self::COVER_DIR, $file);

        $mime = $file->getMimeType();
        $type = str_contains($mime, 'video') ? 'video' : 'image';

        $offer->media()->create([
            'media_path' => $path,
            'media_type' => $type,
            'is_cover' => true,
            'sort_order' => 0,
        ]);
    }

    private function setCover(ServiceOffer $offer, int $mediaId): void
    {
        $offer->media()->update(['is_cover' => false]);

        $offer->media()
            ->where('id', $mediaId)
            ->update(['is_cover' => true]);
    }


    public function delete(ServiceOffer $offer): void
    {

        $offer->loadMissing('media');

        foreach ($offer->media as $media) {
            if ($media->media_path) {
                FileManager::delete($media->media_path);
            }
        }

        $this->serviceOfferRepository->delete($offer);
    }

    private function ensureCoverExists(ServiceOffer $offer): void
    {
        if (!$offer->media()->exists()) {
            return;
        }

        if ($offer->media()->where('is_cover', true)->exists()) {
            return;
        }

        $offer->media()
            ->orderBy('sort_order')
            ->limit(1)
            ->update(['is_cover' => true]);
    }

    /**
     * Add a plan to a provider's service offer.
     *
     * @throws NotFoundHttpException if offer not found or not owned by provider
     */
    public function addPlan(ServiceProvider $provider, int $offerId, array $data): ServiceOfferPlan
    {
        $offer = $this->serviceOfferRepository->findById($offerId, $provider->id);
        if (! $offer) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        $maxOrder = $offer->plans()->max('sort_order');
        $sortOrder = $data['sort_order'] ?? (($maxOrder !== null ? (int) $maxOrder + 1 : 0));
        return $offer->plans()->create([
            'title' => $data['title'] ?? null,
            'price' => $data['price'],
            'currency' => $data['currency'],
            'delivery_time_value' => $data['delivery_time_value'],
            'delivery_time_unit' => $data['delivery_time_unit'],
            'revision_window' => $data['revision_window'] ?? null,
            'number_of_revisions' => $data['number_of_revisions'] ?? null,
            'revision_scope_rules' => $data['revision_scope_rules'] ?? null,
            'sort_order' => (int) $sortOrder,
        ]);
    }

    /**
     * Update a plan belonging to a provider's service offer.
     *
     * @throws NotFoundHttpException if plan not found or not owned by provider
     */
    public function updatePlan(ServiceProvider $provider, int $planId, array $data): ServiceOfferPlan
    {
        $plan = ServiceOfferPlan::with('serviceOffer')->find($planId);
        if (! $plan || (int) $plan->serviceOffer->service_provider_id !== (int) $provider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.plan_not_found'));
        }
        $plan->update($data);
        return $plan->fresh();
    }

    /**
     * Delete a plan belonging to a provider's service offer (plan identified by id only).
     *
     * @throws NotFoundHttpException if plan not found or not owned by provider
     */
    public function deletePlan(ServiceProvider $provider, int $planId): void
    {
        $plan = ServiceOfferPlan::with('serviceOffer')->find($planId);
        if (! $plan || (int) $plan->serviceOffer->service_provider_id !== (int) $provider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.plan_not_found'));
        }
        $plan->delete();
    }

    /**
     * Add an FAQ to a provider's service offer.
     *
     * @throws NotFoundHttpException if offer not found or not owned by provider
     */
    public function addFaq(ServiceProvider $provider, int $offerId, array $data): ServiceOfferFaq
    {
        $offer = $this->serviceOfferRepository->findById($offerId, $provider->id);
        if (! $offer) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        $maxOrder = $offer->faqs()->max('sort_order');
        $sortOrder = $data['sort_order'] ?? (($maxOrder !== null ? (int) $maxOrder + 1 : 0));
        return $offer->faqs()->create([
            'question' => $data['question'],
            'answer' => $data['answer'],
            'sort_order' => (int) $sortOrder,
        ]);
    }

    /**
     * Update an FAQ belonging to a provider's service offer.
     *
     * @throws NotFoundHttpException if FAQ not found or not owned by provider
     */
    public function updateFaq(ServiceProvider $provider, int $faqId, array $data): ServiceOfferFaq
    {
        $faq = ServiceOfferFaq::with('serviceOffer')->find($faqId);
        if (! $faq || (int) $faq->serviceOffer->service_provider_id !== (int) $provider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.faq_not_found'));
        }
        $faq->update($data);
        return $faq->fresh();
    }

    /**
     * Delete an FAQ belonging to a provider's service offer.
     *
     * @throws NotFoundHttpException if FAQ not found or not owned by provider
     */
    public function deleteFaq(ServiceProvider $provider, int $faqId): void
    {
        $faq = ServiceOfferFaq::with('serviceOffer')->find($faqId);
        if (! $faq || (int) $faq->serviceOffer->service_provider_id !== (int) $provider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.faq_not_found'));
        }
        $faq->delete();
    }

    /**
     * Add an available slot to a provider's service offer.
     *
     * @throws NotFoundHttpException if offer not found or not owned by provider
     */
    public function addAvailableSlot(ServiceProvider $provider, int $offerId, array $data): ServiceOfferAvailableSlot
    {
        $offer = $this->serviceOfferRepository->findById($offerId, $provider->id);
        if (! $offer) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        $maxOrder = $offer->availableSlots()->max('sort_order');
        $sortOrder = $data['sort_order'] ?? (($maxOrder !== null ? (int) $maxOrder + 1 : 0));
        return $offer->availableSlots()->create([
            'day' => $data['day'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'sort_order' => (int) $sortOrder,
        ]);
    }

    /**
     * Update an available slot belonging to a provider's service offer.
     *
     * @throws NotFoundHttpException if slot not found or not owned by provider
     */
    public function updateAvailableSlot(ServiceProvider $provider, int $slotId, array $data): ServiceOfferAvailableSlot
    {
        $slot = ServiceOfferAvailableSlot::with('serviceOffer')->find($slotId);
        if (! $slot || (int) $slot->serviceOffer->service_provider_id !== (int) $provider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.available_slot_not_found'));
        }
        $slot->update($data);
        return $slot->fresh();
    }

    /**
     * Delete an available slot belonging to a provider's service offer.
     *
     * @throws NotFoundHttpException if slot not found or not owned by provider
     */
    public function deleteAvailableSlot(ServiceProvider $provider, int $slotId): void
    {
        $slot = ServiceOfferAvailableSlot::with('serviceOffer')->find($slotId);
        if (! $slot || (int) $slot->serviceOffer->service_provider_id !== (int) $provider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.available_slot_not_found'));
        }
        $slot->delete();
    }

    /**
     * Replace all available slots for a service offer with the given flat array.
     * Each item: { day, start_time, end_time }.
     *
     * @param array<int, array{day: string, start_time: string, end_time: string}> $slots
     * @return \Illuminate\Support\Collection<int, ServiceOfferAvailableSlot>
     */
    public function updateAllAvailableSlots(ServiceProvider $provider, int $offerId, array $slots): \Illuminate\Support\Collection
    {
        $offer = $this->serviceOfferRepository->findById($offerId, $provider->id);
        if (! $offer) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        $offer->availableSlots()->delete();
        $created = collect();
        foreach (array_values($slots) as $i => $item) {
            $created->push($offer->availableSlots()->create([
                'day' => $item['day'],
                'start_time' => $item['start_time'] ?? '',
                'end_time' => $item['end_time'] ?? '',
                'sort_order' => $i,
            ]));
        }
        return $created;
    }

    public function ensureOwnedBy(ServiceOffer $offer, ServiceProvider $serviceProvider): void
    {
        if ((int) $offer->service_provider_id !== (int) $serviceProvider->id) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $plans
     * @return array<string, mixed>
     */
    private function normalizeBasePricingFields(array $data, array $plans): array
    {
        if ($plans !== []) {
            $data['base_price'] = null;
            $data['base_currency'] = null;
            $data['base_delivery_time_value'] = null;
            $data['base_delivery_time_unit'] = null;
            $data['base_number_of_revisions'] = null;
            $data['base_revision_window'] = null;
        }

        return $data;
    }
}
