<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\BoostPlan;

interface BoostPlanRepositoryInterface
{
    public function all();

    public function findById($id): ?BoostPlan;

    public function getPaginated(array $filters = []): LengthAwarePaginator;

    public function create(array $data);

    public function update(array $data, BoostPlan $boostPlan);

    public function toggleActive(BoostPlan $boostPlan, bool $status): BoostPlan;

    public function delete(BoostPlan $boostPlan);
}
