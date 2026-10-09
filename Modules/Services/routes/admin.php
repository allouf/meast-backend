<?php

use Illuminate\Support\Facades\Route;
use Modules\Services\Http\Controllers\Admin\BoostCampaignController;
use Modules\Services\Http\Controllers\Admin\BoostPlanController;
use Modules\Services\Http\Controllers\Admin\ProviderProfileController;
use Modules\Services\Http\Controllers\Admin\ProviderVerificationController;
use Modules\Services\Http\Controllers\Admin\ServiceCategoryController;
use Modules\Services\Http\Controllers\Admin\ServiceOfferOrderExtensionReasonController;
use Modules\Services\Http\Controllers\Admin\ServiceOrderRejectionReasonController;

Route::controller(ProviderProfileController::class)->prefix('providers')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show');
});

Route::controller(ProviderVerificationController::class)->prefix('provider-verifications')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show')->whereNumber('id');
    $route->post('{id}/approve', 'approve')->whereNumber('id');
    $route->post('{id}/reject', 'reject')->whereNumber('id');
});

Route::controller(ServiceCategoryController::class)->prefix('service-categories')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show');
    $route->post('create', 'store');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'destroy');
    $route->post('toggle-status/{id}', 'toggleStatus');
});

Route::controller(BoostPlanController::class)->prefix('boost-plans')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show');
    $route->post('create', 'store');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'destroy');
    $route->post('toggle-status/{id}', 'toggleStatus');
});

Route::controller(BoostCampaignController::class)->prefix('boost-campaigns')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show')->whereNumber('id');
    $route->post('{id}/pause', 'pause')->whereNumber('id');
    $route->post('{id}/terminate', 'terminate')->whereNumber('id');
});


Route::controller(ServiceOrderRejectionReasonController::class)
    ->prefix('rejection-reasons')
    ->group(function ($route) {
        $route->get('', 'index');
        $route->get('show/{id}', 'show');
        $route->post('create', 'store');
        $route->delete('delete/{id}', 'destroy');
    });


    Route::controller(ServiceOfferOrderExtensionReasonController::class)
    ->prefix('extension-reasons')
    ->group(function ($route) {
        $route->get('', 'index');
        $route->get('show/{id}', 'show');
        $route->post('create', 'store');
        $route->post('toggle/{id}', 'toggle');
        $route->delete('delete/{id}', 'destroy');
    });