<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserEducation;
use Modules\Job\Repositories\Interfaces\UserEducationRepositoryInterface;

class UserEducationRepository implements UserEducationRepositoryInterface
{
    public function __construct(
        protected UserEducation $model
    ) {}

    public function findById(int $id): ?UserEducation
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

    public function create(array $data): UserEducation
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserEducation $education): UserEducation
    {
        $education->update($data);

        return $education;
    }

    public function delete(UserEducation $education): bool
    {
        return $education->delete();
    }
}
