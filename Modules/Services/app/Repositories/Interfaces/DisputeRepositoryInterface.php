<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\ServiceOfferOrderDispute;

interface DisputeRepositoryInterface
{
    public function all();

    public function findById($id);

    public function create(array $data);

    public function update(array $data, ServiceOfferOrderDispute $dispute);

    public function delete(ServiceOfferOrderDispute $dispute);

    public function addEvidence(ServiceOfferOrderDispute $dispute, array $data);

    public function findByUser(int $userId, int $perPage = 15):LengthAwarePaginator;

    public function findWithDetails(int $id);
}
