<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\User\Account\DeviceController;
use Modules\Core\Http\Controllers\User\Account\UserController;
use Modules\Core\Http\Controllers\User\Auth\AuthController;
use Modules\Core\Http\Controllers\User\Auth\SocialAuthController;
use Modules\Core\Http\Controllers\BetaRegistrationController;
use Modules\Core\Http\Controllers\LanguageController;

Route::controller(BetaRegistrationController::class)->prefix('beta')->group(function ($route) {
    $route->post('register', 'register');
    $route->get('registrations/download', 'downloadExcel');
});

Route::get('languages', [LanguageController::class, 'index']);

Route::controller(SocialAuthController::class)->prefix('auth')->group(function ($route) {
    $route->get('/{provider}/callback/login-success', 'socialLoginSuccess');
    $route->get('/{provider}', 'redirectToProvider');
    $route->get('/{provider}/callback', 'handleProviderCallback');
});

Route::middleware('throttle:auth')->controller(AuthController::class)->group(function ($route) {
    $route->post('register/consumer', 'registerConsumer');
    $route->post('register/company', 'registerCompany');
    $route->post('otp/resend', 'resendOtp');
    $route->post('otp/verify', 'verifyAccount');
    $route->post('refresh', 'refresh');
    $route->post('login', 'login');
    $route->post('password/forgot', 'forgotPassword');
    $route->post('password/verify-otp', 'checkForgotPasswordOtp');
    $route->post('password/reset', 'resetPassword');
});

Route::middleware(['auth:api', 'validate.device'])->group(function ($route) {
    $route->controller(UserController::class)->group(function ($route) {
        $route->get('profile', 'getProfile');
        $route->post('update-profile', 'updateProfile');
        $route->post('update-role', 'updateRole');
        $route->post('profile-photo', 'uploadProfilePhoto');
        $route->delete('delete-photo', 'deleteProfilePhoto');
        $route->delete('delete-account', 'deleteAccount');
    });
});

Route::middleware(['auth:api', 'validate.device', 'profile.complete'])->group(function ($route) {
    $route->controller(AuthController::class)->group(function ($route) {
        $route->post('logout', 'logout');
        $route->post('logout-all', 'logoutAll');
        $route->post('password/change', 'changePassword');
    });
    $route->controller(DeviceController::class)->prefix('devices')->group(function ($route) {
        $route->get('', 'listMyDevices');
        $route->post('revoke-device/{id}', 'revokeDevice');
        $route->post('revoke-all-devices', 'revokeAllDevices');
    });
});
