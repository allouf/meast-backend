<?php

namespace Modules\Store\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Store';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapUserRoutes();
        $this->mapAdminRoutes();
        $this->mapSellerRoutes();
    }

    protected function mapUserRoutes(): void
    {
        Route::middleware(['api', \App\Http\Middleware\LocationMiddleware::class])
            ->prefix('api/v1')
            ->group(module_path($this->name, 'routes/user.php'));
    }

    protected function mapAdminRoutes(): void
    {
        Route::middleware(['api', 'auth:admin', 'role:admin'])
            ->prefix('api/v1/admin')
            ->group(module_path($this->name, 'routes/admin.php'));
    }

    protected function mapSellerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(module_path($this->name, 'routes/seller.php'));
    }
}
