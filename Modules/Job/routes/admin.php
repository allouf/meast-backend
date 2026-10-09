<?php

use Illuminate\Support\Facades\Route;
use Modules\Job\Http\Controllers\Admin\JobCategoryController;
use Modules\Job\Http\Controllers\Admin\JobController as AdminJobController;
use Modules\Job\Http\Controllers\Admin\JobTemplateController;
use Modules\Job\Http\Controllers\Admin\SkillController;

Route::controller(SkillController::class)->prefix('skills')->group(function ($route) {
    $route->get('', 'all');
    $route->get('show/{id}', 'show');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'delete');
    $route->post('toggle-status/{id}', 'toggleStatus');
});
Route::controller(JobCategoryController::class)->prefix('job-categories')->group(function ($route) {
    $route->get('', 'all');
    $route->get('show/{id}', 'show');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'delete');
    $route->post('toggle-status/{id}', 'toggleStatus');
});
Route::controller(JobTemplateController::class)->prefix('job-templates')->group(function ($route) {
    $route->get('', 'all');
    $route->get('show/{id}', 'show');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'delete');
    $route->post('toggle-status/{id}', 'toggleStatus');
});
Route::controller(AdminJobController::class)->prefix('job-postings')->group(function ($route) {
    $route->get('', 'index');
    $route->get('show/{id}', 'show');
    $route->post('create', 'create');
    $route->post('update/{id}', 'update');
    $route->delete('delete/{id}', 'delete');
    $route->post('update-status/{id}', 'updateStatus');
});
