<?php

namespace Modules\Job\Services;

use Modules\Job\Models\UserLanguage;
use Modules\Job\Repositories\Interfaces\UserLanguageRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserLanguageService
{
    public function __construct(
        protected UserLanguageRepositoryInterface $repository
    ) {}

    public function getMyLanguages(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addLanguage(int $userId, array $data): UserLanguage
    {
        return DB::transaction(function () use ($userId, $data) {

            $exists = $this->repository->existsByUserAndLanguage(
                $userId,
                $data['language']
            );

            if ($exists) {
                throw new ConflictHttpException('Language already added');
            }
            $data['user_id'] = $userId;

            return $this->repository->create($data);
        });
    }

    public function updateLanguage(int $userId, int $id, array $data): UserLanguage
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $language = $this->repository->findById($id);

            if (! $language) {
                throw new NotFoundHttpException('Language not found.');
            }

            if ($language->user_id !== $userId) {
                throw new HttpException(403, 'Language does not belong to you.');
            }

            return $this->repository->update($data, $language);
        });
    }

    public function removeLanguage(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $language = $this->repository->findById($id);

            if (! $language) {
                throw new NotFoundHttpException('Language not found.');
            }

            if ($language->user_id !== $userId) {
                throw new HttpException(403, 'Language does not belong to you.');
            }

            return $this->repository->delete($language);
        });
    }
}
