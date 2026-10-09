<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Services\StoreMemberService;
use Modules\Store\Services\StoreMemberPermissionService;
use Modules\Store\Http\Resources\User\StoreMemberResource;
use Exception;
use Modules\Store\Http\Requests\Seller\UpdateMemberRoleRequest;

class StoreMemberController extends Controller
{
    public function __construct(
        protected StoreMemberService $memberService,
        protected StoreMemberPermissionService $permissionService
    ) {}

    /**
     * Get all members of a store
     */
    public function getStoreMembers($storeId)
    {
        try {
            $this->permissionService->requirePermission($storeId, 'members.manage');

            $members = $this->memberService->getStoreMembers($storeId);
            return $this->successResponse('Store members retrieved successfully', StoreMemberResource::collection($members));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get member details
     */
    public function show($memberId)
    {
        try {
            $member = $this->memberService->findById($memberId);
            return $this->successResponse('Member details retrieved successfully', new StoreMemberResource($member));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    /**
     * Update a member's role
     */
    public function updateRole(UpdateMemberRoleRequest $request, $memberId)
    {
        try {
            $data = $request->validated();
            $member = $this->memberService->updateMemberRole($memberId, $data['role']);
            return $this->successResponse('Member role updated successfully', new StoreMemberResource($member));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove a member
     */
    public function remove($memberId)
    {
        try {
            $this->memberService->removeMember($memberId);
            return $this->successResponse('Member removed successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    public function restore($memberId)
    {
        try {
            $this->memberService->restoreMember($memberId);
            return $this->successResponse('Member restored successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }


    public function getRemovedMembers($storeId)
    {
        try {
            $this->permissionService->requirePermission($storeId, 'members.manage');
            $members = $this->memberService->getRemovedMembers($storeId);
            return $this->successResponse('Removed store members retrieved successfully', StoreMemberResource::collection($members));
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}

