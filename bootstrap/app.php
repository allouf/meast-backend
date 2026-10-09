<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Middleware\SetLocaleFromHeader;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/user.php'));
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(base_path('routes/v1/seller.php'));
            Route::middleware('api')
                ->prefix('api/v1/admin')
                ->group(base_path('routes/v1/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            SetLocaleFromHeader::class,
        ]);
        $middleware->alias([
            'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'store.member' => \Modules\Store\Http\Middleware\StoreMemberPermission::class,
            'profile.complete' => \Modules\Core\Http\Middleware\CheckProfileComplete::class,
            'job.profile.candidate' => \Modules\Job\Http\Middleware\CheckJobProfileForCandidate::class,
            'job.profile.company' => \Modules\Job\Http\Middleware\CheckJobProfileForCompany::class,
            'service.profile.provider' => \Modules\Services\Http\Middleware\CheckProviderProfileForService::class,
            'service.category.verification' => \Modules\Services\Http\Middleware\CheckCategoryVerification::class,
            'validate.device' => \Modules\Core\Http\Middleware\ValidateDevice::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('stories:cleanup-expired')->everyMinute();
        $schedule->command('verifications:delete-expired')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
