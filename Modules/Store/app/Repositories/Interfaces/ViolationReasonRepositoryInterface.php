<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\ViolationReason;
use Illuminate\Database\Eloquent\Collection;

interface ViolationReasonRepositoryInterface
{
    public function all(array $filters = []): Collection;
    public function findById(int $id): ViolationReason;
    public function getActive(): Collection;

    public function create(array $data): ViolationReason;

    /**
     * Update an existing violation reason
     *
     * @param int $id
     * @param array $data
     * @return ViolationReason
     */
    public function update(int $id, array $data): ViolationReason;

    /**
     * Delete a violation reason
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
