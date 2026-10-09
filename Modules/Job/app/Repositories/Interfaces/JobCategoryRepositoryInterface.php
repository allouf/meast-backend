<?php

namespace Modules\Job\Repositories\Interfaces;

use Illuminate\Support\Collection;
use Modules\Job\Models\JobCategory;

interface JobCategoryRepositoryInterface
{
/**
     * @return Collection|array{
     *     data: Collection,
     *     meta: array|null
     * }
     */
    public function all(array $filters = []): Collection|array;

    public function find(int $id): ?JobCategory;

    public function findBySlug(string $slug): ?JobCategory;

    public function create(array $data): JobCategory;

    public function update(int $id, array $data): ?JobCategory;

    public function delete(int $id): bool;

    public function hasJobs(int $categoryId): bool;

    public function toggleStatus(int $id): ?JobCategory;
}
