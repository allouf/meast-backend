<?php

namespace Modules\Job\Services;

use Modules\Job\Models\UserWorkExperience;
use Modules\Job\Repositories\Interfaces\UserWorkExperienceRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserWorkExperienceService
{
    public function __construct(
        protected UserWorkExperienceRepositoryInterface $repository
    ) {}

    public function getMyExperiences(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addExperience(int $userId, array $data): UserWorkExperience
    {
        return DB::transaction(function () use ($userId, $data) {
            $data['user_id'] = $userId;
            $data['duration_months'] = $this->calculateDurationMonths($data);

            return $this->repository->create($data);
        });
    }

    public function updateExperience(int $userId, int $id, array $data): UserWorkExperience
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $experience = $this->repository->findById($id);

            if (! $experience) {
                throw new NotFoundHttpException('Work experience not found.');
            }

            if ($experience->user_id !== $userId) {
                throw new HttpException(403, 'Work experience does not belong to you.');
            }

            $merged = array_merge($experience->toArray(), $data);
            $data['duration_months'] = $this->calculateDurationMonths($merged);

            return $this->repository->update($data, $experience);
        });
    }

    public function removeExperience(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $experience = $this->repository->findById($id);

            if (! $experience) {
                throw new NotFoundHttpException('Work experience not found.');
            }

            if ($experience->user_id !== $userId) {
                throw new HttpException(403, 'Work experience does not belong to you.');
            }

            return $this->repository->delete($experience);
        });
    }

    protected function calculateDurationMonths(array $data): ?int
    {
        if (empty($data['start_date'])) {
            return null;
        }

        $start = Carbon::parse($data['start_date']);
        $end = null;

        if (! empty($data['is_current']) && $data['is_current']) {
            $end = Carbon::now();
        } elseif (! empty($data['end_date'])) {
            $end = Carbon::parse($data['end_date']);
        }

        if (! $end || $end->lessThan($start)) {
            return null;
        }

        return $start->diffInMonths($end);
    }
}

