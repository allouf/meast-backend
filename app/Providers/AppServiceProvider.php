<?php

namespace App\Providers;

use Modules\Core\Models\User;
use App\Repositories\Eloquent\AdminRepository;
use App\Repositories\Eloquent\BannerRepository;
use App\Repositories\Eloquent\BetaRegistrationRepository;
use App\Repositories\Eloquent\SliderRepository;
use App\Repositories\Interfaces\AdminRepositoryInterface;
use App\Repositories\Interfaces\BannerRepositoryInterface;
use App\Repositories\Interfaces\BetaRegistrationRepositoryInterface;
use App\Repositories\Interfaces\SliderRepositoryInterface;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
        $this->app->bind(BannerRepositoryInterface::class, BannerRepository::class);
        $this->app->bind(SliderRepositoryInterface::class, SliderRepository::class);
        $this->app->bind(BetaRegistrationRepositoryInterface::class, BetaRegistrationRepository::class);
    }

    public function boot()
    {
        Broadcast::routes(['middleware' => ['auth:sanctum']]);
        require base_path('routes/channels.php');
    }
}
