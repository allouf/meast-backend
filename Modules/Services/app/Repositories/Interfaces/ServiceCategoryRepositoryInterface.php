<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Services\Models\ServiceCategory;

interface ServiceCategoryRepositoryInterface
{
    public function getActiveOrdered(): Collection;

    /** @return LengthAwarePaginator<ServiceCategory> */
    public function getPaginated(array $filters = []): LengthAwarePaginator;

    public function find(int $id): ?ServiceCategory;

    public function create(array $data): ServiceCategory;

    public function update(int $id, array $data): ?ServiceCategory;

    public function delete(int $id): void;

    public function toggleActive(int $id): ServiceCategory;
}
