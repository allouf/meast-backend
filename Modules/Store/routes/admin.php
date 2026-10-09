<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\Admin\StoreController;
use Modules\Store\Http\Controllers\Admin\StoreMemberController;
use Modules\Store\Http\Controllers\Admin\ReviewController;
use Modules\Store\Http\Controllers\Admin\CouponController;
use Modules\Store\Http\Controllers\Admin\PackageController;
use Modules\Store\Http\Controllers\Admin\ProductController;
use Modules\Store\Http\Controllers\Admin\FlashSaleController;
use Modules\Store\Http\Controllers\Admin\OrderController;
use Modules\Store\Http\Controllers\Admin\ViolationController;
use Modules\Store\Http\Controllers\Admin\ViolationReasonController;

Route::middleware(['auth:admin', 'role:admin'])->group(function () {
    Route::controller(StoreController::class)->prefix('stores')->group(function ($route) {
        $route->get('', 'all');
        $route->get('trashed', 'getTrashed');
        $route->get('get-by-user/{id}', 'getByUser');
        $route->post('create', 'create');
        $route->get('show/{id}', 'show');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'delete');
        $route->post('restore/{id}', 'restore');
        $route->delete('force-delete/{id}', 'forceDelete');
        $route->post('switch-activation/{id}', 'switchActivation');
        $route->post('update-status/{id}', 'updateStatus');
        $route->get('export', 'export');
    });
    Route::controller(StoreMemberController::class)->prefix('store-members')->group(function ($route) {
        $route->get('', 'all');
        $route->get('store/{storeId}', 'getByStore');
        $route->get('trashed', 'getTrashed');
        $route->get('show/{id}', 'show');
        $route->post('create', 'create');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'delete');
        $route->post('restore/{id}', 'restore');
    });
    Route::controller(ReviewController::class)->prefix('reviews')->group(function ($route) {
        $route->get('', 'getAll');
        $route->get('show/{reviewId}', 'show');
        $route->post('approve/{id}', 'approve');
    });
    Route::controller(ProductController::class)->prefix('products')->group(function ($route) {
        $route->get('get-by-store/{storeId}', 'getByStore');
        $route->get('', 'index');
        $route->get('show/{id}', 'show');
        $route->delete('delete/{id}', 'delete');
        $route->post('switch-activation/{id}', 'switchActivation');
    });
    Route::controller(PackageController::class)->prefix('packages')->group(function ($route) {
        $route->get('list', 'index');
        $route->get('details/{id}', 'show');
        $route->delete('delete/{id}', 'destroy');
        $route->patch('toggle-activation/{packageId}', 'toggleActivation');
    });
    Route::controller(CouponController::class)->prefix('coupons')->group(function ($route) {
        $route->get('', 'index');
        $route->get('show/{couponId}', 'show');
        $route->delete('delete/{id}', 'destroy');
    });
    Route::controller(FlashSaleController::class)->prefix('flash-sales')->group(function ($route) {
        $route->get('', 'index');
        $route->get('flash-sale/{id}', 'show');
        $route->delete('delete/{id}', 'destroy');
    });
    Route::controller(OrderController::class)->prefix('orders')->group(function ($route) {
        $route->get('', 'index');
        $route->get('show/{masterOrderId}', 'show');
    });
    Route::controller(ViolationController::class)->prefix('violations')->group(function ($route) {
        $route->get('', 'index');
        $route->get('show/{violationId}', 'show');
        $route->post('action/{violationId}', 'takeAction');
    });
    Route::controller(ViolationReasonController::class)->prefix('violation-reasons')->group(function ($route) {
        $route->get('', 'index');
        $route->get('show/{reasonId}', 'show');
        $route->post('create', 'store');
        $route->delete('delete/{reasonId}', 'destroy');
    });
});
