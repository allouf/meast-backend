<?php

namespace Modules\Job\Services;

use Modules\Job\Models\UserEducation;
use Modules\Job\Repositories\Interfaces\UserEducationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserEducationService
{
    public function __construct(
        protected UserEducationRepositoryInterface $repository
    ) {}

    public function getMyEducations(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addEducation(int $userId, array $data): UserEducation
    {
        return DB::transaction(function () use ($userId, $data) {
            $data['user_id'] = $userId;

            return $this->repository->create($data);
        });
    }

    public function updateEducation(int $userId, int $id, array $data): UserEducation
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $education = $this->repository->findById($id);

            if (! $education) {
                throw new NotFoundHttpException('Education not found.');
            }

            if ($education->user_id !== $userId) {
                throw new HttpException(403, 'Education does not belong to you.');
            }

            return $this->repository->update($data, $education);
        });
    }

    public function removeEducation(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $education = $this->repository->findById($id);

            if (! $education) {
                throw new NotFoundHttpException('Education not found.');
            }

            if ($education->user_id !== $userId) {
                throw new HttpException(403, 'Education does not belong to you.');
            }

            return $this->repository->delete($education);
        });
    }
}

