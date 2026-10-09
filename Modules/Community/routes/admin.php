<?php

use Illuminate\Support\Facades\Route;
use Modules\Community\Http\Controllers\Admin\PostController;
use Modules\Community\Http\Controllers\Admin\ReelController;
use Modules\Community\Http\Controllers\Admin\PostReportController;
use Modules\Community\Http\Controllers\Admin\SettingController;

Route::controller(PostController::class)->prefix('posts')->group(function ($route) {
    $route->get('', 'all');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->get('view/{id}', 'view');
    $route->delete('delete/{id}', 'delete');
    $route->post('toggle-status/{id}', 'toggleStatus');
    $route->post('toggle-featured/{id}', 'toggleFeatured');
    $route->post('filter', 'filter');
});
Route::controller(ReelController::class)->prefix('reels')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'destroy');
    $route->post('toggle-featured/{id}', 'toggleFeatured');
    $route->post('change-status/{id}', 'changeStatus');
    $route->post('filter', 'filter');
});
Route::controller(PostReportController::class)->prefix('post-reports')->group(function ($route) {
    $route->get('', 'all');
    $route->get('view/{id}', 'view');
    $route->get('get-by-post/{id}', 'getByPost');
});
Route::controller(SettingController::class)->prefix('settings')->group(function ($route) {
    $route->get('community', 'getCommunitySetting');
    $route->post('community', 'storeOrUpdateCommunitySetting');
    $route->get('suggested-content', 'getSuggestedContentSettings');
    $route->post('suggested-content', 'storeOrUpdateSuggestedContentSetting');
});
