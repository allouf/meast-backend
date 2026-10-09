<?php

namespace Modules\Job\Repositories\Interfaces;

use Illuminate\Support\Collection;
use Modules\Job\Models\Job;

interface JobRepositoryInterface
{
    /** @return array{data: Collection, meta: array} */
    public function all(array $filters = []): array;

    public function find(int $id): ?Job;

    /** @return array{data: Collection, meta: array} */
    public function getByUserId(int $userId, array $filters = []): array;

    public function create(array $data): Job;

    public function update(int $id, array $data): ?Job;

    public function delete(int $id): bool;

    public function updateStatus(int $id, string $status): ?Job;

    public function statistics(?int $employerId = null): array;
}
