<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserEducation;

interface UserEducationRepositoryInterface
{
    public function findById(int $id): ?UserEducation;

    public function findByUserId(int $userId);

    public function create(array $data): UserEducation;

    public function update(array $data, UserEducation $education): UserEducation;

    public function delete(UserEducation $education): bool;
}
