<?php

use App\Http\Controllers\Admin\Auth\AdminController;
use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\Content\CategoryController;
use Modules\Core\Http\Controllers\Admin\Content\GeneralController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Admin\System\StatisticsController;
use App\Http\Controllers\Admin\User\DeviceController;
use Illuminate\Support\Facades\Route;
use Modules\Services\Http\Controllers\Admin\DisputeReasonController;

Route::controller(AuthController::class)->group(function ($route) {
    $route->post('login', 'login')->middleware('throttle:3,1');
    $route->post('refresh', 'refresh');
});
Route::middleware(['auth:admin', 'role:admin'])->group(function () {
    Route::controller(AuthController::class)->group(function ($route) {
        $route->get('profile', 'profile');
        $route->post('logout', 'logout');
        $route->post('update-profile', 'updateProfile');
    });
    Route::controller(AdminController::class)->prefix('admins')->group(function ($route) {
        $route->get('', 'all');
        $route->post('update/{id}', 'update');
        $route->post('create', 'create');
        $route->delete('delete/{id}', 'delete');
    });
    Route::controller(CategoryController::class)->prefix('categories')->group(function ($route) {
        $route->get('', 'all');
        $route->post('create', 'create');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'delete');
        $route->post('switch-activation/{id}', 'switchActivation');
        $route->post('reorder/{id}', 'reorder');
        $route->get('export', 'export');
    });
    Route::controller(GeneralController::class)->prefix('generals')->group(function ($route) {
        $route->get('/', 'getGeneralSettings');
        $route->post('store', 'storeGeneralSetting');
        $route->post('update/{key}', 'updateGeneralSetting');
    });
    Route::controller(UserController::class)->prefix('users')->group(function ($route) {
        $route->get('consumers', 'getConsumers');
        $route->get('consumers/details/{id}', 'getConsumerDetails');
        $route->post('consumers/create', 'createConsumer');
        $route->post('consumers/update/{id}', 'update');
        $route->post('consumers/switch-activation/{id}', 'toggleActive');
        $route->delete('consumers/delete/{id}', 'destroy');
        $route->get('companies', 'getCompanies');
        $route->get('companies/details/{id}', 'getCompanyDetails');
        $route->post('companies/create', 'createCompany');
        $route->post('companies/update/{id}', 'update');
        $route->post('companies/switch-activation/{id}', 'toggleActive');
        $route->delete('companies/delete/{id}', 'destroy');
        $route->get('consumers/export', 'exportConsumers');
        $route->get('companies/export', 'exportCompanies');
    });
    Route::controller(DeviceController::class)->prefix('devices')->group(function ($route) {
        $route->get('', 'listAllDevices');
        $route->get('user/{userId}', 'getDevicesByUser');
        $route->post('revoke-device/{id}', 'revokeDevice');
        $route->post('revoke-devices-for-user/{id}', 'revokeAllDevicesForUser');
    });
    Route::controller(StatisticsController::class)->prefix('dashboard')->group(function ($route) {
        $route->get('statistics', 'statistics');
        $route->get('analytics-by-category', 'analyticsByCategory');
    });

    Route::controller(DisputeReasonController::class)
        ->prefix('dispute-reasons')
        ->group(function ($route) {
            $route->get('', 'index');                     // List + filter (is_active)
            $route->get('show/{id}', 'show');             // Details
            $route->post('create', 'store');              // Create
            $route->post('update/{id}', 'update');        // Update
            $route->post('toggle/{id}', 'toggle');        // Activate / Deactivate
            $route->delete('delete/{id}', 'destroy');     // Delete
        });
});
