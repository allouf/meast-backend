<?php

namespace Modules\Job\Services;

use Modules\Job\Models\UserProject;
use Modules\Job\Repositories\Interfaces\UserProjectRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserProjectService
{
    public function __construct(
        protected UserProjectRepositoryInterface $repository
    ) {}

    public function getMyProjects(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addProject(int $userId, array $data): UserProject
    {
        return DB::transaction(function () use ($userId, $data) {
            $data['user_id'] = $userId;

            return $this->repository->create($data);
        });
    }

    public function updateProject(int $userId, int $id, array $data): UserProject
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $project = $this->repository->findById($id);

            if (! $project) {
                throw new NotFoundHttpException('Project not found.');
            }

            if ($project->user_id !== $userId) {
                throw new HttpException(403, 'Project does not belong to you.');
            }

            return $this->repository->update($data, $project);
        });
    }

    public function removeProject(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $project = $this->repository->findById($id);

            if (! $project) {
                throw new NotFoundHttpException('Project not found.');
            }

            if ($project->user_id !== $userId) {
                throw new HttpException(403, 'Project does not belong to you.');
            }

            return $this->repository->delete($project);
        });
    }
}

