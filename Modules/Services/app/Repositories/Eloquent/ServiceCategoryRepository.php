<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Services\Models\ServiceCategory;
use Modules\Services\Repositories\Interfaces\ServiceCategoryRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceCategoryRepository implements ServiceCategoryRepositoryInterface
{
    public function __construct(
        protected ServiceCategory $model
    ) {}

    /** @inheritdoc */
    public function getActiveOrdered(): Collection
    {
        return $this->model->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /** @inheritdoc */
    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('slug', 'like', '%' . $filters['search'] . '%');
            });
        }
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        $query->orderBy('sort_order')->orderBy('name');
        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage > 0 ? $perPage : 15);
    }

    /** @inheritdoc */
    public function find(int $id): ?ServiceCategory
    {
        return $this->model->find($id);
    }

    /** @inheritdoc */
    public function create(array $data): ServiceCategory
    {
        return $this->model->create($data);
    }

    /** @inheritdoc */
    public function update(int $id, array $data): ?ServiceCategory
    {
        $category = $this->model->find($id);
        if (! $category) {
            return null;
        }
        $category->update($data);
        return $category->fresh();
    }

    /** @inheritdoc */
    public function delete(int $id): void
    {
        $category = $this->model->find($id);
        if (! $category) {
            throw new NotFoundHttpException(__('services::messages.service_categories.not_found'));
        }
        $category->delete();
    }

    /** @inheritdoc */
    public function toggleActive(int $id): ServiceCategory
    {
        $category = $this->model->find($id);
        if (! $category) {
            throw new NotFoundHttpException(__('services::messages.service_categories.not_found'));
        }
        $category->update(['is_active' => ! $category->is_active]);
        return $category->fresh();
    }
}
