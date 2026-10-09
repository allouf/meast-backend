<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserLink;
use Modules\Job\Repositories\Interfaces\UserLinkRepositoryInterface;

class UserLinkRepository implements UserLinkRepositoryInterface
{
    public function __construct(
        protected UserLink $model
    ) {}

    public function findById(int $id): ?UserLink
    {
        return $this->model->find($id);
    }

    public function findByUserId(int $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): UserLink
    {
        return $this->model->create($data);
    }

    public function update(array $data, UserLink $link): UserLink
    {
        $link->update($data);

        return $link;
    }

    public function delete(UserLink $link): bool
    {
        return $link->delete();
    }
}

