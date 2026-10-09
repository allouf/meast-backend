<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Seller\StoreRequest;
use Modules\Store\Http\Resources\User\StoreCategoryResource;
use Modules\Store\Http\Resources\User\StoreResource;
use Modules\Store\Services\StoreService;
use Modules\Store\Services\StoreMemberPermissionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService,
        protected StoreMemberPermissionService $permissionService
    ) {}
    public function getByUser()
    {
        $user = Auth::guard('api')->user();
        $stores = $this->storeService->getByUser($user);
        $storesData = $stores->map(function ($store) {
            $role = $this->permissionService->getCurrentUserRole($store->id);
            $permissions = $this->permissionService->getCurrentUserPermissions($store->id);

            $storeData = (new StoreResource($store))->toArray(request());
            return array_merge($storeData, [
                'role' => $role?->value ?? null,
                'role_display' => $role?->getDisplayName() ?? null,
                'permissions' => $permissions,
            ]);
        });

        return $this->successResponse('Stores retrieved successfully', $storesData);
    }
    public function getDeletedStoresByUser()
    {
        try {
            $user = Auth::guard('api')->user();
            $deletedStores = $this->storeService->getDeletedByUser($user);

            $storesData = $deletedStores->map(function ($store) {
                $role = $this->permissionService->getCurrentUserRole($store->id);
                $permissions = $this->permissionService->getCurrentUserPermissions($store->id);

                $storeData = (new StoreResource($store))->toArray(request());

                return array_merge($storeData, [
                    'role' => $role?->value ?? null,
                    'role_display' => $role?->getDisplayName() ?? null,
                    'permissions' => $permissions,
                ]);
            });

            return $this->successResponse(
                'Deleted stores retrieved successfully',
                $storesData
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Failed to retrieve deleted stores');
        }
    }
    public function show($id)
    {
        try {
            $store = $this->storeService->findById($id);
            $role = $this->permissionService->getCurrentUserRole($id);
            $permissions = $this->permissionService->getCurrentUserPermissions($id);
            $storeData = (new StoreResource($store))->toArray(request());
            return $this->successResponse('Store retrieved successfully', array_merge($storeData, [
                'role' => $role?->value ?? null,
                'role_display' => $role?->getDisplayName() ?? null,
                'permissions' => $permissions,
            ]));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function create(StoreRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::guard('api')->id();
        try {
            $store = $this->storeService->create($data);
            return $this->successResponse('Store created successfully', new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function update(StoreRequest $request, $id)
    {
        try {
            $this->permissionService->requirePermission($id, 'store.settings');
            $store = $this->storeService->findById($id);
            $data = $request->validated();
            $data['user_id'] = Auth::guard('api')->id();
            $user = Auth::guard('api')->user();
            $store = $this->storeService->update($data, $id, $user);
            return $this->successResponse('Store updated successfully', new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function delete($id)
    {
        try {
            $this->permissionService->requirePermission($id, 'store.settings');
            $store = $this->storeService->findById($id);
            $user = Auth::guard('api')->user();
            $this->storeService->delete($id, $user);
            return $this->successResponse('Store deleted successfully');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function restore($id)
    {
        try {
            $this->permissionService->requirePermission($id, 'store.settings');
            $store = $this->storeService->findByIdWithTrashed($id);
            $user = Auth::guard('api')->user();
            $store = $this->storeService->restore($id, $user);
            return $this->successResponse('Store restored successfully', new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function forceDelete($id)
    {
        try {
            $this->permissionService->requirePermission($id, 'store.settings');
            $user = Auth::guard('api')->user();
            $this->storeService->forceDelete($id, $user);
            return $this->successResponse('Store permanently deleted');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function switchActivation($id)
    {
        try {
            $this->permissionService->requirePermission($id, 'store.manage');
            $user = Auth::guard('api')->user();
            $store = $this->storeService->switchActivation($id, $user);
            $status = $store->is_active ? 'activated' : 'deactivated';
            return $this->successResponse("Store {$status} successfully", new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function getCategories($storeId)
    {
        try {
            $this->permissionService->requirePermission($storeId, 'store.manage');
            $categories = $this->storeService->getCategoriesByStore($storeId);
            return $this->successResponse(
                'Store categories retrieved successfully',
                StoreCategoryResource::collection($categories)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'Failed to retrieve store categories');
        }
    }

    public function deleteImage(Request $request, int $storeId)
    {
        $request->validate([
            'type' => 'required|in:logo,cover',
        ]);

        $store = $this->storeService->deleteStoreImage(
            $storeId,
            $request->type
        );

        return $this->successResponse(
            'Store image deleted successfully',
            new StoreResource($store)
        );
    }

    public function uploadImage(Request $request, int $storeId)
    {
        $request->validate([
            'type'  => 'required|in:logo,cover',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $store = $this->storeService->uploadStoreImage(
            $storeId,
            $request->type,
            $request->file('image')
        );

        return $this->successResponse(
            'Store image uploaded successfully',
            new StoreResource($store)
        );
    }
}
