<?php

namespace Modules\Job\Repositories\Interfaces;

use Illuminate\Support\Collection;
use Modules\Job\Models\JobTemplate;

interface JobTemplateRepositoryInterface
{
    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function getByUserId(int $userId, array $filters = []): array|Collection;

    public function find(int $id): ?JobTemplate;

    public function findForUser(int $id, int $userId): ?JobTemplate;

    public function create(array $data): JobTemplate;

    public function update(int $id, array $data): ?JobTemplate;

    public function delete(int $id): bool;

    public function getSystemTemplatesByCategory(int $categoryId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function getSystemTemplates(array $filters = []): array;

    public function getAvailableByCategory(int $categoryId, int $userId, array $filters = []): array;

    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function all(array $filters = []): array;

    public function toggleStatus(int $id): ?JobTemplate;
}
