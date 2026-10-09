<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    App\Providers\RateLimitServiceProvider::class,
    \Modules\Core\Providers\CoreServiceProvider::class,
    \Modules\Services\Providers\ServicesServiceProvider::class,
    \Modules\Advertisement\Providers\AdvertisementServiceProvider::class,
    \Modules\Job\Providers\JobServiceProvider::class,
    \Modules\Ai\Providers\AiServiceProvider::class,
];
