<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserSkill;
use Modules\Job\Repositories\Interfaces\UserSkillRepositoryInterface;

class UserSkillRepository implements UserSkillRepositoryInterface
{
    public function __construct(
        protected UserSkill $model
    ) {}

    public function findById(int $id): ?UserSkill
    {
        return $this->model->find($id);
    }

    public function findByUserId(int $userId)
    {
        return $this->model
            ->with('skill')
            ->where('user_skills.user_id', $userId)
            ->leftJoin('skills', 'user_skills.skill_id', '=', 'skills.id')
            // ->select('user_skills.*','skills.name as skill_name_joined')
            ->select('user_skills.*')
            ->orderBy('skills.name', 'asc')
            ->get();
    }

    public function existsForUser(int $userId, int $skillId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('user_id', $userId)->where('skill_id', $skillId);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    public function create(array $data): UserSkill
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserSkill $skill): UserSkill
    {
        $skill->update($data);

        return $skill;
    }

    public function delete(UserSkill $skill): bool
    {
        return $skill->delete();
    }
}

