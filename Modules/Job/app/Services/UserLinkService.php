<?php

namespace Modules\Job\Services;

use Modules\Job\Models\UserLink;
use Modules\Job\Repositories\Interfaces\UserLinkRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserLinkService
{
    public function __construct(
        protected UserLinkRepositoryInterface $repository
    ) {}

    public function getMyLinks(int $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function addLink(int $userId, array $data): UserLink
    {
        return DB::transaction(function () use ($userId, $data) {
            $data['user_id'] = $userId;

            return $this->repository->create($data);
        });
    }

    public function updateLink(int $userId, int $id, array $data): UserLink
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $link = $this->repository->findById($id);

            if (! $link) {
                throw new NotFoundHttpException('Link not found.');
            }

            if ($link->user_id !== $userId) {
                throw new HttpException(403, 'Link does not belong to you.');
            }

            return $this->repository->update($data, $link);
        });
    }

    public function removeLink(int $userId, int $id): bool
    {
        return DB::transaction(function () use ($userId, $id) {
            $link = $this->repository->findById($id);

            if (! $link) {
                throw new NotFoundHttpException('Link not found.');
            }

            if ($link->user_id !== $userId) {
                throw new HttpException(403, 'Link does not belong to you.');
            }

            return $this->repository->delete($link);
        });
    }
}

