<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserCertification;
use Modules\Job\Repositories\Interfaces\UserCertificationRepositoryInterface;

class UserCertificationRepository implements UserCertificationRepositoryInterface
{
    public function __construct(
        protected UserCertification $model
    ) {}

    public function findById(int $id): ?UserCertification
    {
        return $this->model->find($id);
    }

    public function findByUserId(int $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('issue_date')
            ->get();
    }

    public function create(array $data): UserCertification
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserCertification $certification): UserCertification
    {
        $certification->update($data);

        return $certification;
    }

    public function delete(UserCertification $certification): bool
    {
        return $certification->delete();
    }
}

