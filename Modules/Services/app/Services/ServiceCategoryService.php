<?php

namespace Modules\Services\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Services\Models\ServiceCategory;
use Modules\Services\Repositories\Interfaces\ServiceCategoryRepositoryInterface;

class ServiceCategoryService
{
    public function __construct(
        protected ServiceCategoryRepositoryInterface $serviceCategoryRepository
    ) {}

    public function getActiveOrdered(): Collection
    {
        return $this->serviceCategoryRepository->getActiveOrdered();
    }

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->serviceCategoryRepository->getPaginated($filters);
    }

    public function getById(int $id): ?ServiceCategory
    {
        return $this->serviceCategoryRepository->find($id);
    }

    public function create(array $data): ServiceCategory
    {
        return $this->serviceCategoryRepository->create($data);
    }

    public function update(int $id, array $data): ?ServiceCategory
    {
        return $this->serviceCategoryRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $this->serviceCategoryRepository->delete($id);
    }

    public function toggleStatus(int $id): ServiceCategory
    {
        return $this->serviceCategoryRepository->toggleActive($id);
    }
}
