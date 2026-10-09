<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\DisputeReason;

interface DisputeReasonRepositoryInterface
{
    public function all();

    public function findById($id);

    public function create(array $data);

    public function update(array $data, DisputeReason $disputeReason);

    public function delete(DisputeReason $disputeReason);
}
