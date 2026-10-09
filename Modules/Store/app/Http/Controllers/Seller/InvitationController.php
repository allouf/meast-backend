<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Services\StoreMemberInvitationService;
use Modules\Store\Services\StoreMemberPermissionService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\Store\Http\Requests\Seller\AcceptInvitationRequest;
use Modules\Store\Http\Requests\Seller\ResendInvitationRequest;
use Modules\Store\Http\Requests\Seller\SendInvitationRequest;

class InvitationController extends Controller
{
    public function __construct(
        protected StoreMemberInvitationService $invitationService,
        protected StoreMemberPermissionService $permissionService
    ) {}

    /**
     * Send invitation to a new team member
     */
    public function sendInvitation(SendInvitationRequest $request)
    {
        try {
            $data = $request->validated();

            $this->permissionService->requirePermission($data['store_id'], 'members.invite');

            $invitation = $this->invitationService->sendInvitation($data);
            return $this->successResponse('Invitation sent successfully', $invitation);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    public function resendInvitation(ResendInvitationRequest $request)
    {

        try {
            $data = $request->validated();

            $this->permissionService->requirePermission($data['store_id'], 'members.invite');

            $invitation = $this->invitationService->resendInvitation($data);
            return $this->successResponse('Invitation Resent successfully', $invitation);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    /**
     * Get invitation details for frontend preview
     */
    public function getInvitationPreview(string $token)
    {
        try {
            $member = $this->invitationService->getInvitationByToken($token);

            if (!$member) {
                return $this->errorResponse('Invalid invitation token', 404);
            }

            if ($member->status !== 'invited') {
                return $this->errorResponse('This invitation is no longer valid', 400);
            }

            $expiryDate = $member->created_at->addDays(7);
            if (now()->isAfter($expiryDate)) {
                return $this->errorResponse('This invitation has expired', 400);
            }

            return $this->successResponse('Invitation details retrieved successfully', [
                'token' => $token,
                'member_id' => $member->id,
                'store' => [
                    'id' => $member->store->id,
                    'name' => $member->store->name,
                    'description' => $member->store->description,
                    'logo' => $member->store->logo ? url($member->store->logo) : null,
                ],
                'role' => $member->role->value,
                'role_display' => $member->role->getDisplayName(),
                'role_description' => $member->role->getDescription(),
                'permissions' => $member->role->getPermissions(),
                'permissions_by_category' => $this->getPermissionsByCategory($member->role->getPermissions()),
                'invited_by' => $member->inviter ? [
                    'name' => $member->inviter->name,
                    'email' => $member->inviter->email,
                ] : null,
                'invited_at' => $member->created_at,
                'expires_at' => $expiryDate,
                'email' => $member->email,
                'is_expired' => now()->isAfter($expiryDate),
                'can_accept' => true,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Accept store invitation
     */
    public function acceptInvitation(AcceptInvitationRequest $request, string $token)
    {
        try {
            if (!Auth::check()) {
                return $this->errorResponse('You must be logged in to accept an invitation', 401);
            }

            $userId = Auth::id();
            $result = $this->invitationService->acceptInvitation($token, $userId);

            return $this->successResponse(
                'Invitation accepted successfully. You are now a member of the store.',
                $result
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    /**
     * Revoke a pending invitation
     */
    public function revoke($memberId)
    {
        try {
            $this->invitationService->revokeInvitation($memberId);
            return $this->successResponse('Invitation revoked successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Group permissions by category for better display
     */
    private function getPermissionsByCategory(array $permissions): array
    {
        $categories = [
            'Store Management' => ['store.manage', 'store.settings'],
            'Product Management' => ['products.create', 'products.read', 'products.update', 'products.delete', 'products.pricing'],
            'Inventory Management' => ['inventory.manage'],
            'Order Management' => ['orders.manage'],
            'Customer Management' => ['customers.manage'],
            'Analytics' => ['analytics.view'],
            'Team Management' => ['members.invite', 'members.manage', 'members.remove'],
            'Content Management' => ['policies.manage', 'coupons.manage', 'reviews.manage'],
            'Support' => ['support.access'],
        ];

        $result = [];
        foreach ($categories as $category => $categoryPermissions) {
            $userPermissions = array_intersect($permissions, $categoryPermissions);
            if (!empty($userPermissions)) {
                $result[$category] = $userPermissions;
            }
        }

        return $result;
    }
}
