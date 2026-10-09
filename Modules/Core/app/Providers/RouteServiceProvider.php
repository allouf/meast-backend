<?php

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Core';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapUserRoutes();
    }

    protected function mapUserRoutes(): void
    {
        Route::middleware(['api', \App\Http\Middleware\LocationMiddleware::class])
            ->prefix('api/v1')
            ->group(module_path($this->name, 'routes/user.php'));
    }
}
