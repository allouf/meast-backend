<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Modules\Store\Models\Product;
use Modules\Store\Models\Store;
use Modules\Store\Policies\ProductPolicy;
use Modules\Store\Policies\StorePolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Store::class => StorePolicy::class,
        Product::class => ProductPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gate for Log Viewer access
        Gate::define('viewLogViewer', function ($user = null) {
            // In local/development, allow access (even without authentication)
            if (app()->environment(['local', 'development'])) {
                return true;
            }

            // In production, only allow authenticated admins
            if (auth()->guard('admin')->check()) {
                return true;
            }

            // Also allow authenticated web users in non-production
            if (auth()->check()) {
                return true;
            }

            return false;
        });
    }
}
