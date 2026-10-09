<?php

namespace Modules\Advertisement\Services;

use App\Utilities\FileManager;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Advertisement\Enums\AdvertisementStatus;
use Modules\Advertisement\Library\AdvertisementElasticsearch;
use Modules\Advertisement\Models\Advertisement;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementRepositoryInterface;

class AdvertisementService
{
    public function __construct(
        protected AdvertisementRepositoryInterface $advertisementRepository
    ) {}

    public function getAllAdvertisements($filters = [])
    {
        $useElasticsearch = !empty($filters['search'])
            || !empty($filters['category_id'])
            || !empty($filters['subcategory_id'])
            || !empty($filters['country_code'])
            || !empty($filters['region'])
            || !empty($filters['condition'])
            || isset($filters['min_price'])
            || isset($filters['max_price'])
            || isset($filters['has_images'])
            || isset($filters['is_free'])
            || isset($filters['is_price_negotiable'])
            || !empty($filters['sort']);

        if ($useElasticsearch) {
            try {
                return $this->searchWithElasticsearch($filters);
            } catch (\Throwable $e) {
                report($e);
                return $this->advertisementRepository->all($filters);
            }
        }

        return $this->advertisementRepository->all($filters);
    }

    private function searchWithElasticsearch(array $filters): array
    {
        $es = new AdvertisementElasticsearch();
        $result = $es->searchAdvertisementsWithFilters($filters);

        $resultArray = is_array($result) ? $result : (method_exists($result, 'asArray') ? $result->asArray() : (array) $result);
        $hits = $resultArray['hits']['hits'] ?? [];
        $total = $resultArray['hits']['total']['value'] ?? $resultArray['hits']['total'] ?? 0;
        if (is_array($total)) {
            $total = $total['value'] ?? 0;
        }

        $advertisementIds = collect($hits)->pluck('_id')->map(fn ($id) => (int) $id)->toArray();

        if (empty($advertisementIds)) {
            $page = (int) ($filters['page'] ?? 1);
            $perPage = (int) ($filters['per_page'] ?? 15);
            return [
                'data' => collect([]),
                'meta' => ['total' => 0, 'current_page' => $page, 'last_page' => 0, 'per_page' => $perPage],
            ];
        }

        $advertisements = Advertisement::whereIn('id', $advertisementIds)
            ->with(['user', 'category', 'subcategory'])
            ->get()
            ->sortBy(fn ($ad) => array_search($ad->id, $advertisementIds))
            ->values();

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $paginator = new LengthAwarePaginator(
            $advertisements,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
            ],
        ];
    }

    public function getAdvertisementById($id)
    {
        return $this->advertisementRepository->find($id);
    }

    public function getAdvertisementBySlug($slug)
    {
        return $this->advertisementRepository->findBySlug($slug);
    }

    public function createAdvertisement($data)
    {
        $data['user_id'] = Auth::id();

        if (!isset($data['status'])) {
            $data['status'] = AdvertisementStatus::ACTIVE->value;
        }

        if ($data['status'] === AdvertisementStatus::ACTIVE->value && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (!isset($data['expires_at'])) {
            $data['expires_at'] = now()->addDays(30);
        }

        if (isset($data['images']) && is_array($data['images'])) {
            $uploadedImages = [];
            foreach ($data['images'] as $image) {
                if ($image) {
                    $uploadedImages[] = FileManager::upload('advertisements', $image);
                }
            }
            $data['images'] = $uploadedImages;
        }

        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . time();
        }

        if (!isset($data['country_code']) && Auth::user()->country_code) {
            $data['country_code'] = Auth::user()->country_code;
        }
        if (!isset($data['region']) && Auth::user()->region) {
            $data['region'] = Auth::user()->region;
        }

        return $this->advertisementRepository->create($data);
    }

    public function updateAdvertisement($id, $data)
    {
        $advertisement = $this->advertisementRepository->find($id);

        if (!$advertisement || ($advertisement->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return null;
        }

        if (isset($data['images']) && is_array($data['images'])) {
            $uploadedImages = [];
            foreach ($data['images'] as $image) {
                if ($image) {
                    $uploadedImages[] = FileManager::upload('advertisements', $image);
                }
            }
            $existingImages = $advertisement->images ?? [];
            $mergedImages = array_merge($existingImages, $uploadedImages);
            $data['images'] = array_slice($mergedImages, 0, 10);
        } else {
            unset($data['images']);
        }

        if (isset($data['title']) && $data['title'] !== $advertisement->title) {
            $data['slug'] = Str::slug($data['title']) . '-' . time();
        }

        return $this->advertisementRepository->update($id, $data);
    }

    public function deleteAdvertisement($id)
    {
        $advertisement = $this->advertisementRepository->find($id);

        if (!$advertisement || ($advertisement->user_id !== Auth::id() && !Auth::guard('admin')->check())) {
            return false;
        }

        if ($advertisement->images) {
            foreach ($advertisement->images as $image) {
                FileManager::delete($image);
            }
        }

        return $this->advertisementRepository->delete($id);
    }

    public function getMyAdvertisements($filters = [])
    {
        $filters['user_id'] = Auth::id();

        $useElasticsearch = !empty($filters['search'])
            || !empty($filters['category_id'])
            || !empty($filters['subcategory_id'])
            || !empty($filters['country_code'])
            || !empty($filters['region'])
            || !empty($filters['condition'])
            || isset($filters['min_price'])
            || isset($filters['max_price'])
            || isset($filters['has_images'])
            || isset($filters['is_free'])
            || isset($filters['is_price_negotiable'])
            || !empty($filters['sort']);

        if ($useElasticsearch) {
            try {
                return $this->searchWithElasticsearch($filters);
            } catch (\Throwable $e) {
                report($e);
                return $this->advertisementRepository->getByUser(Auth::id(), $filters);
            }
        }

        return $this->advertisementRepository->getByUser(Auth::id(), $filters);
    }

    public function getAdvertisementsByCategory($categoryId, $filters = [])
    {
        return $this->advertisementRepository->getByCategory($categoryId, $filters);
    }

    public function getAdvertisementsBySubcategory($subcategoryId, $filters = [])
    {
        return $this->advertisementRepository->getBySubcategory($subcategoryId, $filters);
    }

    public function searchAdvertisements($query, $filters = [])
    {
        return $this->advertisementRepository->search($query, $filters);
    }

    public function getSimilarAdvertisements($advertisementId, $categoryId, $limit = 10)
    {
        return $this->advertisementRepository->getSimilar($advertisementId, $categoryId, $limit);
    }

    public function viewAdvertisement($id)
    {
        $advertisement = $this->advertisementRepository->find($id);
        if (!$advertisement) {
            return null;
        }
        $this->advertisementRepository->incrementViews($id);
        return $advertisement->fresh();
    }

    public function updateAdvertisementStatus($id, $status, bool $isAdmin = false)
    {
        $advertisement = $this->advertisementRepository->find($id);
        if (!$advertisement) {
            return null;
        }

        if (!$isAdmin && $advertisement->user_id !== Auth::id()) {
            return null;
        }

        $updateData = ['status' => $status];

        if ($status === AdvertisementStatus::ACTIVE->value) {
            if ($advertisement->status === AdvertisementStatus::ACTIVE->value) {
                if (!$advertisement->canRenew()) {
                    return false;
                }
                $updateData['published_at'] = now();
                $updateData['renewed_at'] = now();
            } else {
                if (!$advertisement->published_at) {
                    $updateData['published_at'] = now();
                }
            }
        }

        return $this->advertisementRepository->update($id, $updateData);
    }

    public function toggleAdvertisementStatus($id)
    {
        $advertisement = $this->advertisementRepository->find($id);
        if (!$advertisement) {
            return null;
        }
        $newStatus = $advertisement->status === AdvertisementStatus::ACTIVE->value
            ? AdvertisementStatus::PAUSED->value
            : AdvertisementStatus::ACTIVE->value;
        return $this->updateAdvertisementStatus($id, $newStatus, true);
    }
}
