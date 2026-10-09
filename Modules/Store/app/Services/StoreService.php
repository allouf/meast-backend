<?php

namespace Modules\Store\Services;

use Modules\Core\Models\User;
use Modules\Store\Repositories\Interfaces\StoreRepositoryInterface;
use Modules\Store\Repositories\Interfaces\StoreMemberRepositoryInterface;
use App\Library\Elasticsearch\Elasticsearch;
use Modules\Store\Library\Elasticsearch\StoreElasticsearch;
use App\Utilities\FileManager;
use Modules\Store\Services\StoreMemberPermissionService;
use Modules\Store\Enums\StoreMemberRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Exception;

class StoreService
{
    protected $storeRepository;
    protected $permissionService;
    protected $storeMemberRepository;
    public function __construct(
        StoreRepositoryInterface $storeRepository,
        StoreMemberPermissionService $permissionService,
        StoreMemberRepositoryInterface $storeMemberRepository,
        protected Elasticsearch $elastic
    ) {
        $this->storeRepository = $storeRepository;
        $this->permissionService = $permissionService;
        $this->storeMemberRepository = $storeMemberRepository;
    }
    public function all(array $params)
    {
        $hasSearch = !empty($params['search']);
        $hasFilters = !empty($params['user_id']) || !empty($params['country_code'])
            || !empty($params['city']) || !empty($params['region'])
            || isset($params['is_active']) || isset($params['is_returnable'])
            || isset($params['min_rating']) || isset($params['max_rating'])
            || isset($params['sort_by']) || isset($params['sort_order']);

        if ($hasSearch || $hasFilters) {
            return $this->searchWithElasticsearch($params);
        }
        return $this->storeRepository->all($params);
    }

    /**
     * Search stores using Elasticsearch
     */
    private function searchWithElasticsearch(array $filters): array
    {
        $storeES = new StoreElasticsearch();

        try {
            $result = $storeES->searchStoresWithFilters($filters);
            $hits = $result['hits'];
            $total = $hits['total']['value'] ?? count($hits['hits']);
            $storeIds = collect($hits['hits'])->pluck('_id')->map(fn($id) => (int)$id)->toArray();

            if (empty($storeIds)) {
                return [
                    'data' => collect(),
                    'meta' => [
                        'current_page' => (int)($filters['page'] ?? 1),
                        'per_page' => (int)($filters['per_page'] ?? 15),
                        'total' => 0,
                        'last_page' => 0,
                    ]
                ];
            }

            $stores = $this->storeRepository->getByIds($storeIds);
            $orderedStores = collect($storeIds)->map(function ($id) use ($stores) {
                return $stores->firstWhere('id', $id);
            })->filter()->values();

            $page = (int)($filters['page'] ?? 1);
            $perPage = (int)($filters['per_page'] ?? 15);

            return [
                'data' => $orderedStores,
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => (int)ceil($total / $perPage),
                ]
            ];
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Elasticsearch search failed, falling back to database', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            return $this->storeRepository->all($filters);
        }
    }
    public function getByUser(User $user)
    {
        $memberStores = $this->storeMemberRepository->getActiveByUser($user->id)->load('store');
        $storeIds = $memberStores->pluck('store_id')->toArray();
        if (empty($storeIds)) {
            return collect();
        }
        $stores = $this->storeRepository->getByIds($storeIds);
        $stores->transform(function ($store) use ($memberStores) {
            $member = $memberStores->firstWhere('store_id', $store->id);
            if ($member) {
                $store->setAttribute('role', $member->role->value);
                $store->setAttribute('permissions', $member->role->getPermissions());
            }
            return $store;
        });
        return $stores;
    }
    public function getDeletedByUser(User $user)
    {
        return $this->storeRepository->getDeletedByUser($user);
    }
    public function getTrashed()
    {
        return $this->storeRepository->getTrashed();
    }
    public function findById(int $id)
    {
        return $this->storeRepository->findById($id);
    }
    public function findByIdWithTrashed(int $id)
    {
        return $this->storeRepository->findByIdWithTrashed($id);
    }
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            if (!empty($data['logo'])) {
                $data['logo'] = FileManager::upload('stores', $data['logo']);
            }
            if (!empty($data['cover'])) {
                $data['cover'] = FileManager::upload('stores', $data['cover']);
            }
            $store = $this->storeRepository->create($data);
            $this->createStoreOwnerMember($store, $data['user_id']);
            if (!empty($data['category_ids'])) {
                $store->categories()->sync($data['category_ids']);
            }
            $store->load(['categories', 'user']);
            $store->loadCount(['categories', 'products']);
            return $store;
        });
    }
    /**
     * Create the initial store owner member when a store is created
     */
    private function createStoreOwnerMember($store, int $userId): void
    {
        $user = User::find($userId);
        $this->storeMemberRepository->create([
            'store_id' => $store->id,
            'user_id' => $userId,
            'role' => StoreMemberRole::STORE_OWNER,
            'status' => 'active',
            'accepted_at' => now(),
        ]);
    }
    public function update(array $data, $id)
    {
        $store = $this->findById($id);
        if (!empty($data['logo'])) {
            $data['logo'] = FileManager::upload('stores', $data['logo'], $store->logo);
        }
        if (!empty($data['cover'])) {
            $data['cover'] = FileManager::upload('stores', $data['cover'], $store->cover);
        }
        if (isset($data['category_ids'])) {
            $store->categories()->sync($data['category_ids']);
        }
        return $this->storeRepository->update($data, $store);
    }
    public function delete($id)
    {
        $store = $this->findById($id);
        if ($store) {
            if ($store->logo) {
                FileManager::delete($store->logo);
            }
            if ($store->cover) {
                FileManager::delete($store->cover);
            }
        }
        return $this->storeRepository->delete($store);
    }
    public function restore($id)
    {
        $store = $this->storeRepository->findByIdWithTrashed($id);
        $this->storeRepository->restore($store);
        $store = $store->fresh(['user', 'categories']);
        $store->loadCount(['categories', 'products']);
        return $store;
    }
    public function forceDelete($id)
    {
        $store = $this->storeRepository->findByIdWithTrashed($id);
        if ($store) {
            if ($store->logo) {
                FileManager::delete($store->logo);
            }
            if ($store->cover) {
                FileManager::delete($store->cover);
            }
        }
        return $this->storeRepository->forceDelete($store);
    }
    public function switchActivation($id)
    {
        $store = $this->storeRepository->findById($id);
        $store->is_active = !$store->is_active;
        $store->save();
        $store = $store->fresh(['user', 'categories']);
        $store->loadCount(['categories', 'products']);
        return $store;
    }
    public function updateStorePolicy(array $data)
    {
        return $this->storeRepository->updateStorePolicy($data);
    }
    public function getPolicy(int $storeId, string $type)
    {
        return $this->storeRepository->getPolicy($storeId, $type);
    }
    public function getCategoriesByStore(int $storeId)
    {
        return $this->storeRepository->getCategoriesByStore($storeId);
    }

    public function updateStatus(int $storeId, string $status, string $reason)
    {
        $store = $this->storeRepository->findById($storeId);
        $store->status = $status;
        $store->status_reason = $reason;
        $store->save();

        // Notify store owner
        $this->notifyStoreOwner($store, $status, $reason);

        return $store->fresh();
    }

    private function notifyStoreOwner($store, string $status, string $reason)
    {
        try {
            $user = $store->user;
            if (!$user) {
                return;
            }

            $statusLabels = [
                'active' => 'Active',
                'warned' => 'Warning',
                'suspended' => 'Suspended',
                'banned' => 'Banned',
            ];

            $statusLabel = $statusLabels[$status] ?? ucfirst($status);

            // Send notification via Firebase
            $notification = new \App\Library\Firebase\FirebaseNotification(
                config('firebase.project_id'),
                config('firebase.credentials_file_path')
            );

            $notification->setTitle([
                'en' => 'Store Status Updated',
                'ar' => 'تم تحديث حالة المتجر'
            ])
                ->setBody([
                    'en' => "Your store '{$store->name}' status has been changed to: {$statusLabel}. Reason: {$reason}",
                    'ar' => "تم تغيير حالة متجرك '{$store->name}' إلى: {$statusLabel}. السبب: {$reason}"
                ])
                ->setUsers(collect([$user]))
                ->push();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to notify store owner about status change: ' . $e->getMessage());
        }
    }

    public function deleteStoreImage(int $storeId, string $type)
    {
        $store = $this->storeRepository->findById($storeId);


        if (!$store->{$type}) {
            return $store; // لا يوجد صورة أساساً
        }

        FileManager::delete($store->{$type});


        $this->storeRepository->update(
            [$type => null],
            $store
        );

        $store->load(['user', 'categories']);
        $store->loadCount(['categories', 'products']);

        return $store;
    }

    public function uploadStoreImage(int $storeId, string $type, UploadedFile $image)
    {
        $store = $this->storeRepository->findById($storeId);


        if (!in_array($type, ['logo', 'cover'])) {
            throw new HttpException(422, 'Invalid image type.');
        }


        if (!empty($store->{$type})) {
            FileManager::delete($store->{$type});
        }

        $path = FileManager::upload(
            "stores/{$type}s",
            $image
        );


        $this->storeRepository->update(
            [$type => $path],
            $store
        );

        $store->load(['user', 'categories']);
        $store->loadCount(['categories', 'products']);

        return $store;
    }
}
