<?php

use Illuminate\Support\Facades\Route;
use Modules\Ai\Http\Controllers\GenerateController;

Route::controller(GenerateController::class)->prefix('ai')->group(function ($r) {
    $r->post('generate', 'generate');
});
