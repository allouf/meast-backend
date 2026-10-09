<?php

namespace Modules\Job\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Illuminate\Support\Collection;
use Modules\Job\Filters\JobCategoryFilter;
use Modules\Job\Models\JobCategory;
use Modules\Job\Repositories\Interfaces\JobCategoryRepositoryInterface;

class JobCategoryRepository implements JobCategoryRepositoryInterface
{
    use PaginateTrait;

    protected JobCategory $model;

    public function __construct(JobCategory $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = []): Collection|array
    {
        $query = $this->model->withCount('jobs');
        $query = (new JobCategoryFilter($filters))->apply($query);
        $shouldPaginate = $filters['paginate'] ?? true;

        if (! $shouldPaginate) {
            return $query->get();

        }
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find(int $id): ?JobCategory
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?JobCategory
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): JobCategory
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?JobCategory
    {
        $category = $this->model->find($id);
        if ($category) {
            $category->update($data);
            return $category->fresh();
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $category = $this->model->find($id);
        return $category ? $category->delete() : false;
    }

    public function hasJobs(int $categoryId): bool
    {
        return $this->model->where('id', $categoryId)->whereHas('jobs')->exists();
    }

    public function toggleStatus(int $id): ?JobCategory
    {
        $category = $this->model->find($id);
        if (! $category) return null;
        $category->update(['is_active' => ! $category->is_active]);
        return $category->fresh();
    }
}
