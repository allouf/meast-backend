<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserProject;
use Modules\Job\Repositories\Interfaces\UserProjectRepositoryInterface;

class UserProjectRepository implements UserProjectRepositoryInterface
{
    public function __construct(
        protected UserProject $model
    ) {}

    public function findById(int $id): ?UserProject
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

    public function create(array $data): UserProject
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserProject $project): UserProject
    {
        $project->update($data);

        return $project;
    }

    public function delete(UserProject $project): bool
    {
        return $project->delete();
    }
}

