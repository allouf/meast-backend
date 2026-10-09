<?php

namespace Modules\Job\Services;

use Illuminate\Support\Facades\DB;
use Modules\Job\Models\Skill;
use Modules\Job\Models\UserSkill;
use Modules\Job\Repositories\Interfaces\UserSkillRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserSkillService
{
    public function __construct(
        protected UserSkillRepositoryInterface $repository
    ) {}

    public function getMySkills(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addSkill(int $userId, array $data): UserSkill
    {
        return DB::transaction(function () use ($userId, $data) {
            $skillId = (int) ($data['skill_id'] ?? 0);
            $skill = Skill::where('id', $skillId)->where('is_active', true)->first();
            if (! $skill) {
                throw new NotFoundHttpException(__('job::messages.skills.not_found'));
            }
            if ($this->repository->existsForUser($userId, $skillId)) {
                throw new HttpException(409, __('job::messages.skills.already_added_to_profile'));
            }
            $data['user_id'] = $userId;
            unset($data['skill_name']);

            return $this->repository->create($data)->load('skill');
        });
    }

    public function updateSkill(int $userId, int $id, array $data): UserSkill
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $userSkill = $this->repository->findById($id);

            if (! $userSkill) {
                throw new NotFoundHttpException(__('job::messages.skills.not_found'));
            }

            if ($userSkill->user_id !== $userId) {
                throw new HttpException(403, __('job::messages.skills.does_not_belong_to_you'));
            }

            $skillId = (int) ($data['skill_id'] ?? 0);
            $skill = Skill::where('id', $skillId)->where('is_active', true)->first();
            if (! $skill) {
                throw new NotFoundHttpException(__('job::messages.skills.not_found'));
            }
            if ($this->repository->existsForUser($userId, $skillId, $id)) {
                throw new HttpException(409, __('job::messages.skills.already_added_to_profile'));
            }
            unset($data['id'], $data['skill_name']);

            return $this->repository->update($data, $userSkill)->load('skill');
        });
    }

    public function removeSkill(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $skill = $this->repository->findById($id);

            if (! $skill) {
                throw new NotFoundHttpException('Skill not found.');
            }

            if ($skill->user_id !== $userId) {
                throw new HttpException(403, 'Skill does not belong to you.');
            }

            return $this->repository->delete($skill);
        });
    }

    /**
     * Sync user skills to match the given list of skill_ids.
     * Removes skills not in the list and adds missing ones.
     *
     * @param  array<int>  $skillIds  List of skill ids (must exist and be active).
     * @return \Illuminate\Support\Collection<int, UserSkill>
     */
    public function syncSkills(int $userId, array $skillIds): \Illuminate\Support\Collection
    {
        return DB::transaction(function () use ($userId, $skillIds) {
            $skillIds = array_values(array_unique(array_map('intval', $skillIds)));
            foreach ($skillIds as $skillId) {
                $skill = Skill::where('id', $skillId)->where('is_active', true)->first();
                if (! $skill) {
                    throw new NotFoundHttpException(__('job::messages.skills.not_found'));
                }
            }

            $current = $this->repository->findByUserId($userId);
            $desiredIds = array_flip($skillIds);
            foreach ($current as $userSkill) {
                if (! isset($desiredIds[$userSkill->skill_id])) {
                    $this->repository->delete($userSkill);
                }
            }
            foreach ($skillIds as $skillId) {
                if (! $this->repository->existsForUser($userId, $skillId)) {
                    $this->repository->create([
                        'user_id' => $userId,
                        'skill_id' => $skillId,
                    ])->load('skill');
                }
            }

            return $this->repository->findByUserId($userId);
        });
    }
}

