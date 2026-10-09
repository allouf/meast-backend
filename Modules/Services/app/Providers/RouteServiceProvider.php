<?php

namespace Modules\Services\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Services';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapUserRoutes();
        $this->mapProviderRoutes();
        $this->mapAdminRoutes();
    }

    /** Public user routes (browse services, etc.) */
    protected function mapUserRoutes(): void
    {
        Route::middleware(['api'])
            ->prefix('api/v1')
            ->group(module_path($this->name, 'routes/user.php'));
    }

    /** Provider dashboard routes (auth required) */
    protected function mapProviderRoutes(): void
    {
        Route::middleware(['api'])
            ->prefix('api/v1')
            ->group(module_path($this->name, 'routes/provider.php'));
    }

    protected function mapAdminRoutes(): void
    {
        Route::middleware(['api', 'auth:admin', 'role:admin'])
            ->prefix('api/v1/admin')
            ->group(module_path($this->name, 'routes/admin.php'));
    }
}
