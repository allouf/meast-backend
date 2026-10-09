<?php

namespace Modules\Store\Http\Controllers\Admin;

use Modules\Store\Exports\StoresExport;
use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\Admin\StoreRequest;
use Modules\Store\Http\Resources\Admin\StoreResource;
use Modules\Store\Services\StoreService;
use Modules\Store\Services\UserService;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StoreController extends Controller
{
    public function __construct(
        protected StoreService $storeService,
        protected UserService $userService
    ) {}
    public function all(Request $request)
    {
        $params = $request->all();
        $stores = $this->storeService->all($params);
        return $this->successResponse(__('store::messages.success'), [
            'data' => StoreResource::collection($stores['data']),
            'meta' => $stores['meta']
        ]);
    }
    public function getByUser($userId)
    {
        $user = $this->userService->findById($userId);
        $stores = $this->storeService->getByUser($user);
        return $this->successResponse(__('store::messages.admin.stores_retrieved_successfully'), StoreResource::collection($stores));
    }
    public function show($id)
    {
        try {
            $store = $this->storeService->findById($id);
            return $this->successResponse(__('store::messages.admin.store_retrieved_successfully'), new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function create(StoreRequest $request)
    {
        $data = $request->validated();
        try {
            $store = $this->storeService->create($data);
            return $this->successResponse(__('store::messages.admin.store_created_successfully'), new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function update(StoreRequest $request, $id)
    {
        try {
            $store = $this->storeService->findById($id);
            $data = $request->validated();

            $store = $this->storeService->update($data, $id);
            return $this->successResponse(__('store::messages.admin.store_updated_successfully'), new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function delete($id)
    {
        try {
            $store = $this->storeService->findById($id);
            $this->storeService->delete($id);
            return $this->successResponse(__('store::messages.admin.store_deleted_successfully'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function getTrashed()
    {
        try {

            $deletedStores = $this->storeService->getTrashed();
            return $this->successResponse(
                'Deleted stores retrieved successfully',
                [
                    'stores' => StoreResource::collection($deletedStores['data']),
                    'meta' => $deletedStores['meta']
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'Failed to retrieve deleted stores');
        }
    }
    public function restore($id)
    {
        try {
            $store = $this->storeService->findByIdWithTrashed($id);
            $store = $this->storeService->restore($id);
            return $this->successResponse(__('store::messages.admin.store_restored_successfully'), new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function forceDelete($id)
    {
        try {
            $this->storeService->forceDelete($id);
            return $this->successResponse(__('store::messages.admin.store_permanently_deleted'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function switchActivation($id)
    {
        try {
            $store = $this->storeService->switchActivation($id);
            $status = $store->is_active ? 'activated' : 'deactivated';
            return $this->successResponse(__('store::messages.admin.store_status_updated_successfully'), new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function export()
    {
        $fileName = 'stores_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new StoresExport, $fileName);
    }

    public function updateStatus(\App\Http\Requests\Admin\Store\UpdateStoreStatusRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $store = $this->storeService->updateStatus($id, $data['status'], $data['reason']);
            return $this->successResponse(__('store::messages.admin.store_status_updated_successfully'), new StoreResource($store));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
