<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserLink;

interface UserLinkRepositoryInterface
{
    public function findById(int $id): ?UserLink;

    public function findByUserId(int $userId);

    public function create(array $data): UserLink;

    public function update(array $data, UserLink $link): UserLink;

    public function delete(UserLink $link): bool;
}
