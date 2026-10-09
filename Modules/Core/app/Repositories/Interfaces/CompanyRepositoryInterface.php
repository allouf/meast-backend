<?php

namespace Modules\Core\Repositories\Interfaces;

use Modules\Core\Models\Company;

interface CompanyRepositoryInterface
{
    /** @param array<string, mixed> $filters */
    public function all(array $filters = []);
    public function create(array $data);
    public function findById(int $id);
    public function findByUserId(int $userId): ?Company;
    public function update(Company $company, array $data);
}
