<?php

namespace Modules\Advertisement\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Advertisement\Enums\AdvertisementStatus;
use Modules\Advertisement\Filters\AdvertisementFilter;
use Modules\Advertisement\Models\Advertisement;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementRepositoryInterface;

class AdvertisementRepository implements AdvertisementRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Advertisement $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->with(['user', 'category', 'subcategory']);
        $query = (new AdvertisementFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find($id)
    {
        return $this->model->with(['user', 'category', 'subcategory'])->find($id);
    }

    public function findBySlug($slug)
    {
        return $this->model->with(['user', 'category', 'subcategory'])
            ->where('slug', $slug)
            ->first();
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $advertisement = $this->model->find($id);
        if ($advertisement) {
            $advertisement->update($data);
            return $advertisement->fresh();
        }
        return null;
    }

    public function delete($id)
    {
        $advertisement = $this->model->find($id);
        if ($advertisement) {
            return $advertisement->delete();
        }
        return false;
    }

    public function getByUser($userId, $filters = [])
    {
        $filters = array_merge($filters, ['sort' => $filters['sort'] ?? 'created_desc']);
        $query = $this->model->with(['user', 'category', 'subcategory'])->where('user_id', $userId);
        $query = (new AdvertisementFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getByCategory($categoryId, $filters = [])
    {
        $query = $this->model->with(['user', 'category', 'subcategory'])
            ->where('advertisement_category_id', $categoryId)
            ->active()
            ->orderBy('published_at', 'desc');

        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getBySubcategory($subcategoryId, $filters = [])
    {
        $query = $this->model->with(['user', 'category', 'subcategory'])
            ->where('advertisement_subcategory_id', $subcategoryId)
            ->active()
            ->orderBy('published_at', 'desc');

        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getActive($filters = [])
    {
        $query = $this->model->with(['user', 'category', 'subcategory'])
            ->active()
            ->orderBy('published_at', 'desc');

        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function search($query, $filters = [])
    {
        $searchQuery = $this->model->with(['user', 'category', 'subcategory'])
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%');
            });
        $searchQuery = (new AdvertisementFilter($filters))->apply($searchQuery);
        return $this->paginate($searchQuery, $filters['per_page'] ?? null);
    }

    public function getSimilar($advertisementId, $categoryId, $limit = 10)
    {
        return $this->model->with(['user', 'category', 'subcategory'])
            ->where('advertisement_category_id', $categoryId)
            ->where('id', '!=', $advertisementId)
            ->active()
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function incrementViews($id)
    {
        return $this->model->where('id', $id)->increment('views_count');
    }

    public function getExpired()
    {
        return $this->model->where('expires_at', '<=', now())
            ->where('status', '!=', AdvertisementStatus::EXPIRED->value)
            ->get();
    }

    public function markAsExpired($id)
    {
        $advertisement = $this->model->find($id);
        if ($advertisement) {
            $advertisement->update(['status' => AdvertisementStatus::EXPIRED->value]);
            return $advertisement;
        }
        return null;
    }
}
