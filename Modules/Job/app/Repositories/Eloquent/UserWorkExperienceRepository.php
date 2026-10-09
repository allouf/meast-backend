<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserWorkExperience;
use Modules\Job\Repositories\Interfaces\UserWorkExperienceRepositoryInterface;

class UserWorkExperienceRepository implements UserWorkExperienceRepositoryInterface
{
    public function __construct(
        protected UserWorkExperience $model
    ) {}

    public function findById(int $id): ?UserWorkExperience
    {
        return $this->model->find($id);
    }

    public function findByUserId(int $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('start_date')
            ->get();
    }

    public function create(array $data): UserWorkExperience
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserWorkExperience $experience): UserWorkExperience
    {
        $experience->update($data);

        return $experience;
    }

    public function delete(UserWorkExperience $experience): bool
    {
        return $experience->delete();
    }
}
