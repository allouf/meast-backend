<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\StoreMemberRepositoryInterface;
use Modules\Store\Enums\StoreMemberRole;
use Modules\Core\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreMemberService
{
    public function __construct(
        protected StoreMemberRepositoryInterface $storeMemberRepository,
        protected StoreMemberPermissionService $permissionService
    ) {}

    public function getStoreMembers(int $storeId)
    {
        return $this->storeMemberRepository->getByStore($storeId);
    }
    public function findById(int $id)
    {
        return $this->storeMemberRepository->findById($id);
    }

    public function updateMemberRole(int $memberId, string $role)
    {
        $member = $this->storeMemberRepository->findById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found');
        }

        $newRole = StoreMemberRole::from($role);
        $this->permissionService->requireCanManageMember($member->store_id, $memberId);

        $this->storeMemberRepository->update($memberId, ['role' => $newRole]);

        return $this->storeMemberRepository->findById($memberId);
    }

    public function removeMember(int $memberId): bool
    {
        $member = $this->storeMemberRepository->findById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found');
        }

        $this->permissionService->requirePermission($member->store_id, 'members.remove');
        $this->permissionService->requireCanManageMember($member->store_id, $memberId);

        return $this->storeMemberRepository->delete($memberId);
    }
    public function restoreMember(int $memberId): bool
    {
        $member = $this->storeMemberRepository->findTrashedById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found or not deleted');
        }

        $this->permissionService->requirePermission($member->store_id, 'members.remove');
        $this->permissionService->requireCanManageMember($member->store_id, $memberId);

        return $this->storeMemberRepository->restore($memberId);
    }

    public function revokeInvitation(int $memberId): bool
    {
        $member = $this->storeMemberRepository->findById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found');
        }

        if ($member->status === 'active') {
            throw new HttpException(400, 'Cannot revoke invitation for active members');
        }

        return $this->storeMemberRepository->revokeInvitation($memberId, Auth::id());
    }

    public function getRemovedMembers(int $storeId)
    {
        $this->permissionService->requirePermission($storeId, 'members.manage');
        return $this->storeMemberRepository->getRemovedByStore($storeId);
    }

    public function getAllMembers($search = null, $storeId = null, $status = null, $role = null, $paginate = true)
    {
        return $this->storeMemberRepository->all($search, $storeId, $status, $role, $paginate);
    }
    public function createMember(array $data)
    {
        if (isset($data['user_id'])) {
            $user = User::find($data['user_id']);
            if (!$user) {
                throw new NotFoundHttpException('User not found.');
            }
            $data['email'] = $user->email;
        }

        return $this->storeMemberRepository->create($data);
    }
    public function updateMember(int $id, array $data)
    {
        $this->storeMemberRepository->update($id, $data);
        return $this->storeMemberRepository->findById($id);
    }

    public function deleteMember(int $id): bool
    {
        return $this->storeMemberRepository->delete($id);
    }

    public function restoreMemberById(int $id): bool
    {
        return $this->storeMemberRepository->restore($id);
    }

    public function getTrashedMembers($search = null, $storeId = null, $status = null, $role = null, $paginate = true)
    {
        return $this->storeMemberRepository->getTrashed($search, $storeId, $status, $role, $paginate);
    }
}
