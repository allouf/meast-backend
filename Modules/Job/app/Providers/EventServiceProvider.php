<?php

namespace Modules\Job\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array<string, array<int, string>> */
    protected $listen = [];

    protected static bool $shouldDiscoverEvents = true;

    protected function configureEmailVerification(): void {}
}
