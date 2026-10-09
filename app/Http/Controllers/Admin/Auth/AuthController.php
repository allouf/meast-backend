<?php

namespace App\Http\Controllers\Admin\Auth;

use Exception;
use Modules\Core\Models\Admin;
use Illuminate\Http\Request;
use App\Services\AdminService;
use Modules\Core\Services\TokenService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Admin\Auth\AuthRequest;
use App\Http\Resources\Admin\User\AdminResource;
use App\Http\Requests\Admin\Auth\UpdateAdminProfileRequest;

class AuthController extends Controller
{
    protected $adminService;
    protected $tokenService;

    public function __construct(AdminService $adminService, TokenService $tokenService)
    {
        $this->adminService = $adminService;
        $this->tokenService = $tokenService;
    }

    public function login(AuthRequest $request)
    {
        try {
            $credentials = $request->validated();

            $admin = Admin::where('email', $credentials['email'])->first();

            if ($admin && Hash::check($credentials['password'], $admin->password)) {
                $tokens = $this->tokenService->generateTokens($admin);

                return $this->successResponse(__('login_successful'), [
                    'admin' => new AdminResource($admin),
                    'tokens' => $tokens,
                ], 200);
            }

            return $this->errorResponse(__('unauthorized'), 401);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function logout(Request $request)
    {
        try {
            $refreshToken = $request->input('refresh_token');
            if ($refreshToken) {
                $this->tokenService->invalidateAdminRefreshToken($refreshToken);
            }
            $this->tokenService->invalidateCurrentToken();
            return $this->successResponse(__('logged_out_successfully'));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function profile()
    {
        try {
            $admin = Auth::guard('admin')->user();
            return $this->successResponse(__('fetch_success'), new AdminResource($admin));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateProfile(UpdateAdminProfileRequest $request)
    {
        try {
            $admin = Auth::guard('admin')->user();
            $updated = $this->adminService->updateProfile($request->validated(), $admin->id);

            return $this->successResponse(__('profile_updated_successfully'), new AdminResource($updated));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->input('refresh_token');
            if (!$refreshToken) {
                return $this->errorResponse(__('messages.admin.refresh_token_required'), 400);
            }

            $admin = $this->tokenService->validateAdminRefreshToken($refreshToken);

            $tokens = $this->tokenService->generateTokens(
                $admin,
                null,
                null,
                null,
                $refreshToken
            );

            return $this->successResponse(__('messages.admin.token_refreshed'), [
                'admin' => new AdminResource($admin),
                'tokens' => $tokens,
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
