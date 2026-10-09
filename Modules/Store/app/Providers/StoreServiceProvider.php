<?php

namespace Modules\Store\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Store\Repositories\Eloquent\CartRepository;
use Modules\Store\Repositories\Eloquent\CategoryRepository;
use Modules\Store\Repositories\Eloquent\CouponRepository;
use Modules\Store\Repositories\Eloquent\FlashSaleRepository;
use Modules\Store\Repositories\Eloquent\MasterOrderRepository;
use Modules\Store\Repositories\Eloquent\PackageRepository;
use Modules\Store\Repositories\Eloquent\ProductRepository;
use Modules\Store\Repositories\Eloquent\ReviewRepository;
use Modules\Store\Repositories\Eloquent\SlaRuleRepository;
use Modules\Store\Repositories\Eloquent\StoreMemberRepository;
use Modules\Store\Repositories\Eloquent\StoreOrderRepository;
use Modules\Store\Repositories\Eloquent\StoreRepository;
use Modules\Store\Repositories\Eloquent\TicketRepository;
use Modules\Store\Repositories\Eloquent\ViolationReasonRepository;
use Modules\Store\Repositories\Eloquent\UserAddressRepository;
use Modules\Store\Repositories\Eloquent\ViolationRepository;
use Modules\Store\Repositories\Eloquent\WishlistRepository;
use Modules\Store\Repositories\Interfaces\CartRepositoryInterface;
use Modules\Store\Repositories\Interfaces\CategoryRepositoryInterface;
use Modules\Store\Repositories\Interfaces\CouponRepositoryInterface;
use Modules\Store\Repositories\Interfaces\FlashSaleRepositoryInterface;
use Modules\Store\Repositories\Interfaces\MasterOrderRepositoryInterface;
use Modules\Store\Repositories\Interfaces\PackageRepositoryInterface;
use Modules\Store\Repositories\Interfaces\ProductRepositoryInterface;
use Modules\Store\Repositories\Interfaces\ReviewRepositoryInterface;
use Modules\Store\Repositories\Interfaces\SlaRuleRepositoryInterface;
use Modules\Store\Repositories\Interfaces\StoreMemberRepositoryInterface;
use Modules\Store\Repositories\Interfaces\StoreOrderRepositoryInterface;
use Modules\Store\Repositories\Interfaces\StoreRepositoryInterface;
use Modules\Store\Repositories\Interfaces\TicketRepositoryInterface;
use Modules\Store\Repositories\Interfaces\ViolationReasonRepositoryInterface;
use Modules\Store\Repositories\Interfaces\UserAddressRepositoryInterface;
use Modules\Store\Repositories\Interfaces\ViolationRepositoryInterface;
use Modules\Store\Repositories\Interfaces\WishlistRepositoryInterface;

class StoreServiceProvider extends ServiceProvider
{
    protected string $name = 'Store';
    protected string $nameLower = 'store';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerObservers();
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
        $this->registerRepositories();
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerRepositories(): void
    {
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(CouponRepositoryInterface::class, CouponRepository::class);
        $this->app->bind(FlashSaleRepositoryInterface::class, FlashSaleRepository::class);
        $this->app->bind(MasterOrderRepositoryInterface::class, MasterOrderRepository::class);
        $this->app->bind(PackageRepositoryInterface::class, PackageRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
        $this->app->bind(SlaRuleRepositoryInterface::class, SlaRuleRepository::class);
        $this->app->bind(StoreMemberRepositoryInterface::class, StoreMemberRepository::class);
        $this->app->bind(StoreOrderRepositoryInterface::class, StoreOrderRepository::class);
        $this->app->bind(StoreRepositoryInterface::class, StoreRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(UserAddressRepositoryInterface::class, UserAddressRepository::class);
        $this->app->bind(ViolationReasonRepositoryInterface::class, ViolationReasonRepository::class);
        $this->app->bind(ViolationRepositoryInterface::class, ViolationRepository::class);
        $this->app->bind(WishlistRepositoryInterface::class, WishlistRepository::class);
    }

    protected function registerObservers(): void
    {
        \Modules\Store\Models\Store::observe(\Modules\Store\Observers\StoreObserver::class);
        \Modules\Store\Models\Product::observe(\Modules\Store\Observers\ProductObserver::class);
        \Modules\Store\Models\ProductVariant::observe(\Modules\Store\Observers\ProductVariantObserver::class);
        \Modules\Store\Models\ProductReview::observe(\Modules\Store\Observers\ProductReviewObserver::class);
    }
}
