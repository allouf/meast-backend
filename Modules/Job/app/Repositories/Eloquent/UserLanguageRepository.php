<?php

namespace Modules\Job\Repositories\Eloquent;

use Modules\Job\Models\UserLanguage;
use Modules\Job\Repositories\Interfaces\UserLanguageRepositoryInterface;

class UserLanguageRepository implements UserLanguageRepositoryInterface
{
    public function __construct(
        protected UserLanguage $model
    ) {}

    public function findById(int $id): ?UserLanguage
    {
        return $this->model->find($id);
    }

    public function findByUserId(int $userId)
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('language')
            ->get();
    }

    public function create(array $data): UserLanguage
    {
        return $this->model->create($data);
    }

    public function existsByUserAndLanguage(int $userId, string $language): bool
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('language', $language)
            ->exists();
    }

    public function update(array $data, UserLanguage $language): UserLanguage
    {
        $language->update($data);

        return $language;
    }

    public function delete(UserLanguage $language): bool
    {
        return $language->delete();
    }
}
