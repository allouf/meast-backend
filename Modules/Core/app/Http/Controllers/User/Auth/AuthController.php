<?php

namespace Modules\Core\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Http\Requests\User\Auth\ChangePasswordRequest;
use Modules\Core\Http\Requests\User\Auth\CheckOtpRequest;
use Modules\Core\Http\Requests\User\Auth\CompanyRequest;
use Modules\Core\Http\Requests\User\Auth\ConsumerRequest;
use Modules\Core\Http\Resources\User\Account\CompanyResource;
use Modules\Core\Http\Resources\User\Account\ConsumerResource;
use Modules\Core\Models\UserDevice;
use Modules\Core\Services\CompanyService;
use Modules\Core\Services\TokenService;
use Modules\Core\Services\UserService;
use Modules\Core\Services\VerificationService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected CompanyService $companyService,
        protected VerificationService $verificationService,
        protected TokenService $tokenService
    ) {}

    protected function registerUser(array $data, string $role)
    {
        try {
            $user = match ($role) {
                'consumer' => $this->userService->createConsumer($data),
                'company' => $this->companyService->createCompany($data)->user,
            };
            $this->verificationService->sendVerification($user, 'verify');
            $resource = match ($role) {
                'consumer' => new ConsumerResource($user),
                'company' => new CompanyResource($user->company),
            };
            return $this->successResponse(__('core::messages.auth.registered_successfully', ['role' => ucfirst($role)]), $resource);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function registerConsumer(ConsumerRequest $request)
    {
        return $this->registerUser($request->validated(), 'consumer');
    }

    public function registerCompany(CompanyRequest $request)
    {
        return $this->registerUser($request->validated(), 'company');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['contact' => 'required|string', 'password' => 'required|string']);
        $user = $this->userService->getUserByContact($data['contact']);
        if (!$user) return $this->errorResponse(__('core::messages.user_not_found'), 401);
        if (!$user->is_active) return $this->errorResponse(__('core::messages.auth.account_inactive'), 403);
        if (!Hash::check($data['password'], $user->password)) return $this->errorResponse(__('core::messages.auth.invalid_credentials'), 401);
        $tokens = null;
        if (!is_null($user->verified_at)) {
            $tokens = $this->tokenService->generateTokens($user, $request->header('User-Agent'), $request->ip());
        }
        return $this->successResponse(__('core::messages.auth.login_successful'), [
            'user' => $this->getUserResource($user),
            'tokens' => $tokens,
        ]);
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->input('refresh_token');
        try {
            $user = $this->tokenService->validateRefreshToken($refreshToken);
            $device = UserDevice::where('user_id', $user->id)
                ->where('refresh_token', hash('sha256', $refreshToken))
                ->first();
            if (!$device) {
                return $this->errorResponse(__('core::messages.auth.device_not_found_or_revoked'), 403);
            }
            $tokens = $this->tokenService->generateTokens($user, null, null, $device->id, $refreshToken);
            return $this->successResponse(__('core::messages.auth.token_refreshed'), [
                'user' => $this->getUserResource($user),
                'tokens' => $tokens,
            ]);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function logout()
    {
        $this->tokenService->invalidateCurrentToken();
        return $this->successResponse(__('core::messages.auth.logged_out_successfully'));
    }

    public function logoutAll()
    {
        $this->tokenService->invalidateAllTokens(auth()->user());
        return $this->successResponse(__('core::messages.auth.logged_out_all_devices_successfully'));
    }

    public function resendOtp(Request $request)
    {
        try {
            $request->validate(['contact' => 'required|string']);
            $user = $this->verificationService->getUserByContact($request->contact);
            if (!$user) return $this->errorResponse(__('core::messages.user_not_found'), 404);
            $this->verificationService->resendOtp($user, 'verify');
            return $this->successResponse(__('core::messages.auth.otp_resent_successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function verifyAccount(CheckOtpRequest $request)
    {
        $data = $request->validated();
        if (!$this->verificationService->checkOtp($data['contact'], $data['code'])) {
            return $this->errorResponse(__('core::messages.auth.invalid_or_expired_otp'), 422);
        }
        $user = $this->verificationService->getUserByContact($data['contact']);
        $tokens = $this->tokenService->generateTokens($user, request()->header('User-Agent'), request()->ip());
        return $this->successResponse(__('core::messages.auth.otp_verified_successfully'), [
            'user' => $this->getUserResource($user),
            'tokens' => $tokens,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['contact' => 'required|string']);
        $user = $this->userService->getUserByContact($request->contact);
        if (!$user) return $this->errorResponse(__('core::messages.user_not_found'), 404);
        $this->verificationService->sendVerification($user, 'reset_password');
        return $this->successResponse(__('core::messages.auth.otp_sent_successfully'));
    }

    public function checkForgotPasswordOtp(Request $request)
    {
        try {
            $data = $request->validate(['contact' => 'required|string', 'code' => 'required|string|size:6']);
            $result = $this->verificationService->checkOtp($data['contact'], $data['code'], 'reset_password');
            if ($result === false) {
                return $this->errorResponse(__('core::messages.auth.invalid_or_expired_otp'), 422);
            }
            return $this->successResponse(__('core::messages.auth.otp_verified_successfully'), ['reset_token' => $result]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $data = $request->validate([
                'contact' => 'required|string',
                'reset_token' => 'required|string',
                'new_password' => 'required|string|min:6|confirmed',
            ]);
            if (!$this->verificationService->verifyPasswordResetToken($data['contact'], $data['reset_token'])) {
                return $this->errorResponse(__('core::messages.auth.invalid_or_expired_reset_token'), 422);
            }
            $user = $this->verificationService->getUserByContact($data['contact']);
            if (!$user) return $this->errorResponse(__('core::messages.user_not_found'), 404);
            $this->userService->resetPassword($user, $data['new_password']);
            $this->verificationService->deletePasswordResetToken($data['contact']);
            return $this->successResponse(__('core::messages.auth.password_reset_successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $data = $request->validated();
            $user = auth()->user();
            $this->userService->changePassword($user, $data['current_password'], $data['new_password']);
            return $this->successResponse(__('core::messages.auth.password_changed_successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    protected function getUserResource($user)
    {
        $user->loadMissing(['roles', 'company']);
        $role = $user->getRoleNames()->first();
        if ($role === null && !$user->roles()->exists()) {
            $user->assignRole('consumer');
            $role = 'consumer';
        }
        return match ($role) {
            'consumer' => new ConsumerResource($user),
            'company' => $user->company ? new CompanyResource($user->company) : new ConsumerResource($user),
            default => new ConsumerResource($user),
        };
    }
}
