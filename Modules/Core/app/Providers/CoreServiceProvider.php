<?php

namespace Modules\Core\Providers;

use Modules\Core\Models\User;
use Illuminate\Support\ServiceProvider;
use Modules\Core\Observers\UserObserver;
use Modules\Core\Repositories\Eloquent\CompanyRepository;
use Modules\Core\Repositories\Eloquent\UserRepository;
use Modules\Core\Repositories\Interfaces\CompanyRepositoryInterface;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;

class CoreServiceProvider extends ServiceProvider
{
    protected string $name = 'Core';
    protected string $nameLower = 'core';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        User::observe(UserObserver::class);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->commands([
            \Modules\Core\Console\Commands\DeleteExpiredVerifications::class,
        ]);
    }
}
