<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\UserLanguage;

interface UserLanguageRepositoryInterface
{
    public function findById(int $id): ?UserLanguage;

    public function findByUserId(int $userId);

    public function create(array $data): UserLanguage;

    public function update(array $data, UserLanguage $language): UserLanguage;

    public function delete(UserLanguage $language): bool;

    public function existsByUserAndLanguage(int $userId, string $language): bool;
}
