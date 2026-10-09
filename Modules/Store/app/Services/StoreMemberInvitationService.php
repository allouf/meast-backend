<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\StoreMemberRepositoryInterface;
use Modules\Store\Repositories\Interfaces\StoreRepositoryInterface;
use Modules\Store\Mail\StoreInvitationMail;
use Modules\Store\Models\StoreMember;
use Modules\Store\Enums\StoreMemberRole;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StoreMemberInvitationService
{
    protected $storeMemberRepository;
    protected $storeRepository;

    public function __construct(
        StoreMemberRepositoryInterface $storeMemberRepository,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->storeMemberRepository = $storeMemberRepository;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Send invitation to a new team member
     */
    public function sendInvitation(array $data): array
    {
        $store = $this->storeRepository->findById($data['store_id']);
        if (!$store) {
            throw new NotFoundHttpException('Store not found'); // 404
        }
        $member = $this->storeMemberRepository->checkEmailExistsInUsers($data['email']);

        if (!$member) {
            throw new HttpException(400, 'This email does not exist in users, or the user has not provided this email in their profile.'); // 400
        }

        if ($this->storeMemberRepository->checkEmailExistsInStore($member->id, $data['store_id'])) {
            throw new HttpException(400, 'This email is already associated with this store'); // 400
        }
        $this->validateRoleAssignment($data['role'], $data['store_id']);

        $invitationData = [
            'store_id' => $data['store_id'],
            'user_id' => $member->id,
            'role' => $data['role'],
            'status' => 'invited',
            'invitation_token' => Str::random(64),
            'invited_by' => Auth::id(),
        ];

        $member = $this->storeMemberRepository->create($invitationData);

        $this->sendInvitationEmail($member, $data['email']);

        return [
            'id' => $member->id,
            'email' => $member->email,
            'role' => $member->role->value,
            'role_display' => $member->role->getDisplayName(),
            'permissions' => $member->role->getPermissions(),
            'status' => $member->status,
            'invited_at' => $member->created_at->format('Y-m-d H:i:s'),
            'invite_url' => $this->generateInviteUrl($member->invitation_token),
        ];
    }
    public function resendInvitation(array $data)
    {
        $member = $this->storeMemberRepository->findByEmailAndStore(
            $data['email'],
            $data['store_id']
        );

        if (!$member) {
            throw new NotFoundHttpException('No invitation found for this email in this store.');
        }
        if ($member->status !== 'invited') {
            throw new HttpException(400, "Invitation cannot be resent because the current status is: {$member->status}");
        }
        $updateData = [
            'invitation_token' => Str::random(64),
            'invited_by' => Auth::id(),
            'created_at' => now(),
        ];
        $updatedMember = $this->storeMemberRepository->update($member->id, $updateData);
        $this->sendInvitationEmail($updatedMember, $data['email']);
        return [
            'id' => $updatedMember->id,
            'email' => $data['email'],
            'status' => $updatedMember->status,
            'invited_at' => $updatedMember->created_at->format('Y-m-d H:i:s'),
            'invite_url' => $this->generateInviteUrl($updatedMember->invitation_token),
        ];
    }
    /**
     * Accept invitation
     */
    public function acceptInvitation(string $token, int $userId): array
    {
        $member = $this->storeMemberRepository->findByToken($token);
        if (!$member) {
            throw new NotFoundHttpException('Invalid invitation token'); // 404
        }

        if ($member->status !== 'invited') {
            throw new HttpException(400, 'This invitation is no longer valid'); // 400
        }

        if ($userId !== $member->user_id) {
            throw new HttpException(400, 'User mismatch. Please use the email address that received the invitation.'); // 400
        }

        $this->storeMemberRepository->update($member->id, [
            'user_id' => $userId,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        return [
            'id' => $member->id,
            'store_id' => $member->store_id,
            'role' => $member->role->value,
            'role_display' => $member->role->getDisplayName(),
            'permissions' => $member->role->getPermissions(),
            'status' => 'active',
            'accepted_at' => now(),
        ];
    }

    /**
     * Revoke invitation
     */
    public function revokeInvitation(int $memberId): bool
    {
        $member = $this->storeMemberRepository->findById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found'); // 404
        }

        if ($member->status === 'active') {
            throw new HttpException(400, 'Cannot revoke invitation for active members. Use remove member instead.'); // 400
        }

        return $this->storeMemberRepository->revokeInvitation($memberId, Auth::id());
    }

    /**
     * Remove team member
     */
    public function removeMember(int $memberId): bool
    {
        $member = $this->storeMemberRepository->findById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found'); // 404
        }

        if ($member->isOwner()) {
            $ownersCount = $this->storeMemberRepository->getStoreOwners($member->store_id)->count();
            if ($ownersCount <= 1) {
                throw new HttpException(400, 'Cannot remove the last store owner'); // 400
            }
        }

        return $this->storeMemberRepository->delete($memberId);
    }

    /**
     * Update member role
     */
    public function updateMemberRole(int $memberId, StoreMemberRole $newRole): array
    {
        $member = $this->storeMemberRepository->findById($memberId);
        if (!$member) {
            throw new NotFoundHttpException('Member not found'); // 404
        }

        $this->validateRoleAssignment($newRole->value, $member->store_id, $memberId);

        $this->storeMemberRepository->update($memberId, [
            'role' => $newRole,
        ]);

        $updatedMember = $this->storeMemberRepository->findById($memberId);

        return [
            'id' => $updatedMember->id,
            'email' => $updatedMember->email,
            'role' => $updatedMember->role->value,
            'role_display' => $updatedMember->role->getDisplayName(),
            'permissions' => $updatedMember->role->getPermissions(),
            'status' => $updatedMember->status,
        ];
    }

    /**
     * Get store team members
     */
    public function getStoreMembers(int $storeId): array
    {
        $members = $this->storeMemberRepository->getByStore($storeId);
        return $members->map(function ($member) {
            return [
                'id' => $member->id,
                'email' => $member->email,
                'user' => $member->user ? [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'photo' => $member->user->photo,
                ] : null,
                'role' => $member->role->value,
                'role_display' => $member->role->getDisplayName(),
                'permissions' => $member->role->getPermissions(),
                'status' => $member->status,
                'invited_at' => $member->created_at,
                'accepted_at' => $member->accepted_at,
                'invited_by' => $member->inviter ? [
                    'id' => $member->inviter->id,
                    'name' => $member->inviter->name,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Validate role assignment
     */
    private function validateRoleAssignment(string $role, int $storeId, ?int $excludeMemberId = null): void
    {
        $currentUser = Auth::user();
        $roleEnum = StoreMemberRole::from($role);
        $currentUserMember = $this->storeMemberRepository->findByEmailAndStore($currentUser->email, $storeId);

        if (!$currentUserMember) {
            throw new HttpException(403, 'You are not a member of this store'); // 403 forbidden
        }

        if (!$currentUserMember->canManageRole($roleEnum)) {
            throw new HttpException(403, 'You do not have permission to assign this role'); // 403 forbidden
        }

        if ($roleEnum === StoreMemberRole::STORE_OWNER) {
            $existingOwners = $this->storeMemberRepository->getStoreOwners($storeId);
            if ($excludeMemberId) {
                $existingOwners = $existingOwners->reject(fn($owner) => $owner->id === $excludeMemberId);
            }
            if ($existingOwners->count() >= 5) {
                throw new HttpException(400, 'Maximum number of store owners reached (5)'); // 400
            }
        }
    }

    /**
     * Send invitation email
     */
    private function sendInvitationEmail(StoreMember $member, string $email): void
    {
        $inviteUrl = $this->generateInviteUrl($member->invitation_token);
        Mail::to($email)->send(new StoreInvitationMail($member, $inviteUrl));
    }

    /**
     * Get invitation by token
     */
    public function getInvitationByToken(string $token): ?StoreMember
    {
        return $this->storeMemberRepository->findByToken($token);
    }

    /**
     * Generate invitation URL
     */
    private function generateInviteUrl(string $token): string
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'https://uat.middleeastcommerce.net'), '/');
        $token = urlencode($token);
        return "{$frontendUrl}/en/stores/invitation/?token={$token}";
    }
}
