<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserProject;

interface UserProjectRepositoryInterface
{
    public function findById(int $id): ?UserProject;

    public function findByUserId(int $userId);

    public function create(array $data): UserProject;

    public function update(array $data, UserProject $project): UserProject;

    public function delete(UserProject $project): bool;
}
