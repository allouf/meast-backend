<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserSkill;

interface UserSkillRepositoryInterface
{
    public function findById(int $id): ?UserSkill;

    public function findByUserId(int $userId);

    public function existsForUser(int $userId, int $skillId, ?int $excludeId = null): bool;

    public function create(array $data): UserSkill;

    public function update(array $data, UserSkill $skill): UserSkill;

    public function delete(UserSkill $skill): bool;
}
