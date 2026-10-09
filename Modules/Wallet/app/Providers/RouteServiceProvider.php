<?php

namespace Modules\Wallet\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * Wires the Wallet module's HTTP routes.
 *
 * Mirrors the per-persona pattern used by Modules/Store: separate route files
 * for User, Admin, and (where relevant) Seller, each mapped under /api/v1
 * with their own middleware stack.
 *
 * The Wallet module currently exposes User-facing endpoints only. Admin and
 * Seller route files will be added when those personas need wallet visibility.
 */
class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Wallet';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapUserRoutes();
        $this->mapWebhookRoutes();
    }

    /**
     * User routes: per-user wallet read + write endpoints.
     * All routes inside routes/user.php are expected to use the auth:api
     * middleware (declared per-route inside the file, matching how Modules/Store
     * does it).
     */
    protected function mapUserRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(module_path($this->name, 'routes/user.php'));
    }

    /**
     * Webhook routes: external services post here. NO auth:api — request
     * signatures (e.g. Stripe-Signature) are verified inside the controller.
     */
    protected function mapWebhookRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/v1/wallet/webhooks')
            ->group(module_path($this->name, 'routes/webhooks.php'));
    }
}
