<?php

namespace Modules\Core\Http\Controllers\User\Account;

use App\Http\Controllers\Controller;
use Modules\Core\Models\User;
use Modules\Core\Services\TokenService;
use Modules\Core\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Core\Http\Requests\User\Account\DeleteAccountRequest;
use Modules\Core\Http\Resources\User\Account\CompanyResource;
use Modules\Core\Http\Resources\User\Account\ConsumerResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Exception;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected TokenService $tokenService
    ) {}

    public function getProfile()
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $this->errorResponse(__('core::messages.user_not_found'));
        }
        return $this->successResponse(__('core::messages.success'), ['user' => $this->getUserResource($user)]);
    }

    public function updateProfile()
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $this->errorResponse(__('core::messages.user_not_found'));
        }
        $request = $this->getRoleRequest($user);
        $data = $request->validated();
        $updated = $this->userService->updateProfile($user, $data);
        return $this->successResponse(__('core::messages.account.profile_updated_successfully'), [
            'user' => $this->getUserResource($updated),
        ]);
    }

    protected function getRoleRequest($user)
    {
        return match (true) {
            $user->hasRole('consumer') => app(\Modules\Core\Http\Requests\User\Auth\ConsumerUpdateRequest::class),
            $user->hasRole('company') => app(\Modules\Core\Http\Requests\User\Auth\CompanyUpdateRequest::class),
            default => throw new HttpException(403, __('core::messages.auth.invalid_role')),
        };
    }

    protected function getUserResource($user)
    {
        return match (true) {
            $user->hasRole('consumer') => new ConsumerResource($user),
            $user->hasRole('company') && $user->company?->id => new CompanyResource($user->company),
            $user->hasRole('company') => new ConsumerResource($user),
            default => throw new HttpException(403, __('core::messages.auth.invalid_role')),
        };
    }

    public function updateRole(Request $request)
    {
        $data = $request->validate(['role' => 'required']);
        try {
            $this->userService->updateRole(Auth::user(), $data['role']);
            return $this->successResponse(__('core::messages.account.role_updated_successfully'));
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteAccount(DeleteAccountRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();
            if (!$user) {
                return $this->errorResponse(__('core::messages.user_not_found'), Response::HTTP_NOT_FOUND);
            }
            $data = $request->validated();
            $this->userService->deleteAccount($user, $data['password'], $data['reason'] ?? null);
            $this->tokenService->invalidateAllTokens($user);
            return $this->successResponse(__('core::messages.account.account_deleted_successfully'));
        } catch (ValidationException $e) {
            return $this->exceptionResponse($e);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, __('core::messages.account.delete_account_error'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteAccountWeb(Request $request)
    {
        try {
            $data = $request->validate([
                'identifier' => 'required|string',
                'password' => 'required|string',
                'confirm' => 'required|accepted'
            ]);
            $user = User::where('email', $data['identifier'])
                ->orWhere('phone', $data['identifier'])
                ->first();
            if (!$user) {
                return response()->json(['message' => __('core::messages.account.user_not_found_identifier')], 404);
            }
            if (!Hash::check($data['password'], $user->password)) {
                return response()->json(['message' => __('core::messages.account.invalid_password')], 422);
            }
            $this->userService->deleteAccount($user, $data['password'], $data['reason'] ?? null);
            $this->tokenService->invalidateAllTokens($user);
            $frontendUrl = config('app.frontend_url', 'https://uat.middleeastcommerce.net');
            return response()->json([
                'message' => __('core::messages.account.account_deleted_successfully'),
                'redirect_url' => $frontendUrl
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting your account: ' . $e->getMessage()], 500);
        }
    }

    public function deleteProfilePhoto()
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $this->errorResponse(__('core::messages.user_not_found'));
        }
        $updatedUser = $this->userService->deleteProfilePhoto($user);
        return $this->successResponse(
            __('core::messages.account.profile_photo_deleted_successfully'),
            ['user' => $this->getUserResource($updatedUser)]
        );
    }

    public function uploadProfilePhoto()
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return $this->errorResponse(__('core::messages.user_not_found'));
        }
        if (!request()->hasFile('photo')) {
            return $this->errorResponse(__('core::messages.account.no_photo_uploaded'), 422);
        }
        $updatedUser = $this->userService->uploadProfilePhoto($user, request()->file('photo'));
        return $this->successResponse(
            __('core::messages.account.profile_photo_updated_successfully'),
            ['user' => $this->getUserResource($updatedUser)]
        );
    }
}
