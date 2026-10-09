<?php

use Illuminate\Support\Facades\Route;
use Modules\Advertisement\Http\Controllers\Admin\AdvertisementCategoryController;
use Modules\Advertisement\Http\Controllers\Admin\AdvertisementController;
use Modules\Advertisement\Http\Controllers\Admin\AdvertisementReportController;
use Modules\Advertisement\Http\Controllers\Admin\AdvertisementSubcategoryController;

Route::controller(AdvertisementCategoryController::class)->prefix('advertisement-categories')->group(function ($route) {
    $route->get('', 'all');
    $route->get('show/{id}', 'show');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'delete');
    $route->post('toggle-status/{id}', 'toggleStatus');
});
Route::controller(AdvertisementSubcategoryController::class)->prefix('advertisement-subcategories')->group(function ($route) {
    $route->get('', 'all');
    $route->get('show/{id}', 'show');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'delete');
    $route->post('toggle-status/{id}', 'toggleStatus');
});
Route::controller(AdvertisementReportController::class)->prefix('advertisement-reports')->group(function ($route) {
    $route->get('', 'all');
    $route->get('show/{id}', 'show');
    $route->post('update-status/{id}', 'updateStatus');
});
Route::controller(AdvertisementController::class)->prefix('advertisements')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show');
    $route->post('update-status/{id}', 'updateStatus');
});
