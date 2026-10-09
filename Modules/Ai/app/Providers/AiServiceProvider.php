<?php

namespace Modules\Ai\Providers;

use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;

class AiServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Ai';
    protected string $nameLower = 'ai';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(
            module_path($this->name, 'config/config.php'),
            'ai'
        );
        $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
    }
}
