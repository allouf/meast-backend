<?php

use Illuminate\Support\Facades\Route;
use Modules\Advertisement\Http\Controllers\User\AdvertisementCategoryController;
use Modules\Advertisement\Http\Controllers\User\AdvertisementController;
use Modules\Advertisement\Http\Controllers\User\AdvertisementFavoriteController;
use Modules\Advertisement\Http\Controllers\User\AdvertisementReportController;
use Modules\Advertisement\Http\Controllers\User\AdvertisementSubcategoryController;

// Public routes (no auth)
Route::controller(AdvertisementCategoryController::class)->prefix('advertisement-categories')->group(function ($route) {
    $route->get('', 'index');
    $route->get('active', 'active');
    $route->get('with-subcategories', 'withSubcategories');
    $route->get('{id}', 'show');
});
Route::controller(AdvertisementSubcategoryController::class)->prefix('advertisement-subcategories')->group(function ($route) {
    $route->get('', 'index');
    $route->get('active', 'active');
    $route->get('category/{categoryId}', 'byCategory');
    $route->get('{id}', 'show');
});
Route::controller(AdvertisementController::class)->prefix('advertisements')->group(function ($route) {
    $route->get('', 'index');
    $route->get('search', 'search');
    $route->get('show/{id}', 'show');
    $route->get('{id}/similar', 'similar');
});

// Authenticated user routes
Route::middleware(['auth:api', 'validate.device', 'profile.complete'])->group(function ($route) {
    Route::controller(AdvertisementController::class)->prefix('advertisements')->group(function ($route) {
        $route->post('', 'store')->middleware('throttle:3,1');
        $route->get('my-advertisements', 'myAdvertisements');
        $route->post('{id}', 'update');
        $route->delete('{id}', 'destroy');
        $route->post('{id}/update-status', 'updateStatus');
    });
    Route::controller(AdvertisementFavoriteController::class)->prefix('advertisement-favorites')->group(function ($route) {
        $route->post('toggle', 'toggle');
        $route->get('', 'index');
        $route->get('check/{advertisementId}', 'check');
    });
    Route::controller(AdvertisementReportController::class)->prefix('advertisement-reports')->group(function ($route) {
        $route->post('', 'store')->middleware('throttle:5,1');
        $route->get('my-reports', 'myReports');
        $route->get('{id}', 'show');
        $route->get('check/{advertisementId}', 'check');
    });
});
