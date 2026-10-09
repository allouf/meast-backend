<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserWorkExperience;

interface UserWorkExperienceRepositoryInterface
{
    public function findById(int $id): ?UserWorkExperience;

    public function findByUserId(int $userId);

    public function create(array $data): UserWorkExperience;

    public function update(array $data, UserWorkExperience $experience): UserWorkExperience;

    public function delete(UserWorkExperience $experience): bool;
}
