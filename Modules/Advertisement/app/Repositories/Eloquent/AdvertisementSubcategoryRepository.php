<?php

namespace Modules\Advertisement\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Advertisement\Filters\AdvertisementSubcategoryFilter;
use Modules\Advertisement\Models\AdvertisementSubcategory;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementSubcategoryRepositoryInterface;

class AdvertisementSubcategoryRepository implements AdvertisementSubcategoryRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(AdvertisementSubcategory $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->with('category')->withCount('advertisements')
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc');
        $query = (new AdvertisementSubcategoryFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find($id)
    {
        return $this->model->with('category')->withCount('advertisements')->find($id);
    }

    public function findBySlug($slug)
    {
        return $this->model->with('category')->withCount('advertisements')->where('slug', $slug)->first();
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $subcategory = $this->model->find($id);
        if ($subcategory) {
            $subcategory->update($data);
            return $subcategory->fresh();
        }
        return null;
    }

    public function delete($id)
    {
        $subcategory = $this->model->find($id);
        if ($subcategory) {
            return $subcategory->delete();
        }
        return false;
    }

    public function hasAdvertisements(int $subcategoryId): bool
    {
        return $this->model->where('id', $subcategoryId)->whereHas('advertisements')->exists();
    }

    public function getByCategory($categoryId)
    {
        return $this->model->where('advertisement_category_id', $categoryId)
            ->active()
            ->orderBy('sort_order', 'asc')
            ->get();
    }

    public function getActive($filters = [])
    {
        $query = $this->model->with('category')->active()->orderBy('sort_order', 'asc');
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function toggleStatus(int $id)
    {
        $subcategory = $this->model->find($id);
        if (!$subcategory) {
            return null;
        }
        $subcategory->update(['is_active' => !$subcategory->is_active]);
        return $subcategory->fresh();
    }
}
