<?php

namespace Modules\Job\Services;

use Modules\Job\Models\UserCertification;
use Modules\Job\Repositories\Interfaces\UserCertificationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserCertificationService
{
    public function __construct(
        protected UserCertificationRepositoryInterface $repository
    ) {}

    public function getMyCertifications(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addCertification(int $userId, array $data): UserCertification
    {
        return DB::transaction(function () use ($userId, $data) {
            $data['user_id'] = $userId;

            return $this->repository->create($data);
        });
    }

    public function updateCertification(int $userId, int $id, array $data): UserCertification
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $certification = $this->repository->findById($id);

            if (! $certification) {
                throw new NotFoundHttpException('Certification not found.');
            }

            if ($certification->user_id !== $userId) {
                throw new HttpException(403, 'Certification does not belong to you.');
            }

            return $this->repository->update($data, $certification);
        });
    }

    public function removeCertification(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $certification = $this->repository->findById($id);

            if (! $certification) {
                throw new NotFoundHttpException('Certification not found.');
            }

            if ($certification->user_id !== $userId) {
                throw new HttpException(403, 'Certification does not belong to you.');
            }

            return $this->repository->delete($certification);
        });
    }
}

