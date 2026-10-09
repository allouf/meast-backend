<?php

namespace Modules\Store\Repositories\Eloquent;

use Modules\Core\Models\User;
use App\Traits\PaginateTrait;
use Modules\Store\Filters\StoreFilter;
use Modules\Store\Models\Store;
use Modules\Store\Models\StorePolicy;
use Modules\Store\Repositories\Interfaces\StoreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository implements StoreRepositoryInterface
{
    use PaginateTrait;
    protected $model;

    public function __construct(Store $model)
    {
        $this->model = $model;
    }

    public function all(array $params)
    {
        $builder = (new StoreFilter($params))->apply($this->model->query());
        $query = $builder->with(['user', 'categories'])->withCount(['categories', 'products']);

        return $this->paginate($query);
    }


    public function getByUser(User $user)
    {
        return $this->model
            ->with(['user', 'categories'])
            ->withCount(['categories', 'products'])
            ->where('user_id', $user->id)
            ->get();
    }

    public function getDeletedByUser(User $user)
    {
        return $this->model
        ->onlyTrashed()
        ->with(['user','categories'])
        ->withCount(['categories', 'products'])
        ->where('user_id', $user->id)
        ->get();
    }
    public function getTrashed()
    {
        $query = $this->model->onlyTrashed()->with(['user', 'categories'])->withCount(['categories', 'products'])->orderBy('id', 'desc');
        return $this->paginate($query);
    }
    public function findById(int $id)
    {
        return $this->model->with(['user', 'categories'])->withCount(['categories', 'products'])->findOrFail($id);
    }

    public function findByIdWithTrashed(int $id)
    {
        return $this->model->with(['user', 'categories'])->withCount(['categories', 'products'])->withTrashed()->findOrFail($id);
    }

    public function create(array $data)
    {
        $store = $this->model->create($data);
        return $store;
    }

    public function update($data, \Modules\Store\Models\Store $store)
    {
        $store->update($data);
        $store->load(['user', 'categories']);
        $store->loadCount(['categories', 'products']);
        return $store;
    }

    public function delete(Store $store)
    {
        return $store->delete();
    }

    public function restore(Store $store)
    {
        return $store->restore();
    }

    public function forceDelete(Store $store)
    {
        return $store->forceDelete();
    }
    public function updateStorePolicy(array $data)
    {
        return StorePolicy::updateOrCreate(
            [
                'store_id' => $data['store_id'],
                'type' => $data['type']
            ],
            [
                'content' => $data['content']
            ]
        );
    }

    public function getPolicy(int $storeId, string $type)
    {
        return StorePolicy::where('store_id', $storeId)
            ->where('type', $type)
            ->first();
    }

    public function getByIds(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }

        return $this->model->with(['user', 'categories'])->withCount(['categories', 'products'])->whereIn('id', $ids)->get();
    }


    public function getCategoriesByStore(int $storeId)
    {
        $store = $this->model->with('categories')->findOrFail($storeId);
        return $store->categories;
    }
}
