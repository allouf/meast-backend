<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserCertification;

interface UserCertificationRepositoryInterface
{
    public function findById(int $id): ?UserCertification;

    public function findByUserId(int $userId);

    public function create(array $data): UserCertification;

    public function update(array $data, UserCertification $certification): UserCertification;

    public function delete(UserCertification $certification): bool;
}
