<?php

namespace Modules\Advertisement\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Advertisement\Filters\AdvertisementCategoryFilter;
use Modules\Advertisement\Models\AdvertisementCategory;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementCategoryRepositoryInterface;

class AdvertisementCategoryRepository implements AdvertisementCategoryRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(AdvertisementCategory $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->withCount(['subcategories', 'advertisements'])->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
        $query = (new AdvertisementCategoryFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find($id)
    {
        return $this->model->find($id);
    }

    public function findBySlug($slug)
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $category = $this->model->find($id);
        if ($category) {
            $category->update($data);
            return $category->fresh();
        }
        return null;
    }

    public function delete($id)
    {
        $category = $this->model->find($id);
        if ($category) {
            return $category->delete();
        }
        return false;
    }

    public function hasAdvertisements(int $categoryId): bool
    {
        return $this->model->where('id', $categoryId)->whereHas('advertisements')->exists();
    }

    public function toggleStatus(int $id)
    {
        $category = $this->model->find($id);
        if (!$category) {
            return null;
        }
        $category->update(['is_active' => !$category->is_active]);
        return $category->fresh();
    }

    public function getActive($filters = [])
    {
        $query = $this->model->active()->with('subcategories')->orderBy('sort_order', 'asc');
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getWithSubcategories($filters = [])
    {
        return $this->model->with(['subcategories' => function ($query) {
            $query->active()->orderBy('sort_order', 'asc');
        }])->active()->orderBy('sort_order', 'asc')->paginate($filters['per_page'] ?? null);
    }
}
