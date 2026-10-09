<?php

namespace Modules\Store\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use Modules\Store\Services\StoreMemberService;
use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Admin\StoreMemberRequest;
use Modules\Store\Http\Resources\Admin\StoreMemberResource;

class StoreMemberController extends Controller
{
    protected $storeMemberService;

    public function __construct(StoreMemberService $storeMemberService)
    {
        $this->storeMemberService = $storeMemberService;
    }

    /**
     * Get all store members with filters and pagination
     */
    public function all(Request $request)
    {
        try {
            $paginate = $request->boolean('paginate', false);
            $search = $request->get('search');
            $storeId = $request->get('store_id');
            $status = $request->get('status');
            $role = $request->get('role');

            $members = $this->storeMemberService->getAllMembers($search, $storeId, $status, $role, $paginate);

            $response = [
                'members' => StoreMemberResource::collection($members['data'])
            ];

            if ($paginate && $members['meta']) {
                $response['meta'] = $members['meta'];
            }

            return $this->successResponse(__('store::messages.fetch_success'), $response);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get members by store ID
     */
    public function getByStore(Request $request, $storeId)
    {
        try {
            $members = $this->storeMemberService->getStoreMembers($storeId);
            return $this->successResponse(__('store::messages.fetch_success'), [
                'members' => StoreMemberResource::collection($members)
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get a specific store member
     */
    public function show($id)
    {
        try {
            $member = $this->storeMemberService->findById($id);
            return $this->successResponse(__('store::messages.fetch_success'), new StoreMemberResource($member));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create a new store member
     */
    public function create(StoreMemberRequest $request)
    {
        try {
            $data = $request->validated();
            $member = $this->storeMemberService->createMember($data);
            return $this->successResponse(__('store::messages.create_success'), new StoreMemberResource($member));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    /**
     * Update a store member
     */
    public function update(StoreMemberRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $member = $this->storeMemberService->updateMember($id, $data);
            return $this->successResponse(__('store::messages.update_success'), new StoreMemberResource($member));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete a store member
     */
    public function delete($id)
    {
        try {
            $this->storeMemberService->deleteMember($id);
            return $this->successResponse(__('store::messages.delete_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Restore a deleted store member
     */
    public function restore($id)
    {
        try {
            $this->storeMemberService->restoreMemberById($id);
            return $this->successResponse(__('store::messages.restore_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get all trashed store members with filters and pagination
     */
    public function getTrashed(Request $request)
    {
        try {
            $paginate = $request->boolean('paginate', false);
            $search = $request->get('search');
            $storeId = $request->get('store_id');
            $status = $request->get('status');
            $role = $request->get('role');

            $members = $this->storeMemberService->getTrashedMembers($search, $storeId, $status, $role, $paginate);

            $response = [
                'members' => StoreMemberResource::collection($members['data'])
            ];

            if ($paginate && $members['meta']) {
                $response['meta'] = $members['meta'];
            }

            return $this->successResponse(__('store::messages.fetch_success'), $response);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
