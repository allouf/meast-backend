<?php

namespace Modules\Advertisement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Advertisement';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     */
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapUserRoutes();
        $this->mapAdminRoutes();
        $webPath = module_path($this->name, '/routes/web.php');
        if (file_exists($webPath)) {
            $this->mapWebRoutes();
        }
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes(): void
    {
        $apiPath = module_path($this->name, '/routes/api.php');
        if (file_exists($apiPath)) {
            Route::middleware('api')->prefix('api')->name('api.')->group($apiPath);
        }
    }

    /**
     * User advertisement routes (api/v1, with location middleware).
     */
    protected function mapUserRoutes(): void
    {
        Route::middleware(['api', \App\Http\Middleware\LocationMiddleware::class])
            ->prefix('api/v1')
            ->group(module_path($this->name, '/routes/user.php'));
    }

    /**
     * Admin advertisement routes (api/v1/admin, auth + role).
     */
    protected function mapAdminRoutes(): void
    {
        Route::middleware(['api', 'auth:admin', 'role:admin'])
            ->prefix('api/v1/admin')
            ->group(module_path($this->name, '/routes/admin.php'));
    }
}
