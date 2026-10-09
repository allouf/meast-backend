<?php

namespace App\Http\Controllers\Admin\Auth;

use Exception;
use Illuminate\Http\Request;
use App\Services\AdminService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\General\AdminRequest;
use App\Http\Requests\Admin\General\UpdateAdminRequest;
use App\Http\Resources\Admin\User\AdminResource;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function all()
    {
        $admins = $this->adminService->all();
        return $this->successResponse(__('fetch_success'), AdminResource::collection($admins));
    }
    public function create(AdminRequest $request)
    {
        try {
            $admin = $this->adminService->create($request->validated());
            return $this->successResponse(__('create_success'), new AdminResource($admin));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function update(UpdateAdminRequest $request ,$id)
    {
        try {
            $admin = $this->adminService->update($request->validated() ,$id);
            return $this->successResponse(__('update_success'), new AdminResource($admin));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function delete($id)
    {
        try {
            $this->adminService->delete($id);
            return $this->successResponse(__('delete_success'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    
}
