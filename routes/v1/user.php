<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\HomeController;
use Modules\Core\Http\Controllers\User\Settings\SharedController;
use Modules\Core\Http\Controllers\LocationController;
use Modules\Job\Http\Controllers\User\CompanyProfileController;

// Auth, account and devices are in Modules\Core (Core module routes)
Route::middleware([\App\Http\Middleware\LocationMiddleware::class])->group(function () {

    Route::controller(HomeController::class)->prefix('home')->group(function ($route) {
        $route->get('/carousel', 'carousel');
        $route->get('/featured-products', 'featured');
        $route->get('/banners', 'banners');
        $route->get('/top-offers', 'topOffers');
        $route->get('/popular-categories', 'popularCategories');
        $route->get('/best-seller-categories', 'bestSellerCategories');
        $route->get('/best-seller-products', 'bestSellerProducts');
        $route->get('/unique-products', 'uniqueProducts');
        $route->get('/suggested-products', 'suggestedProducts');
        $route->get('/sponsored-categories', 'sponsoredCategories');
        $route->get('/sponsored-products', 'sponsoredProducts');
        $route->get('/discounted-products', 'discountedProducts');
        $route->get('/pre-order', 'preOrder');
        $route->get('/introducing-products', 'introducingProducts');
        $route->get('/free-shipping-products', 'freeShippingProducts');
    });
    Route::controller(SharedController::class)->prefix('shared')->group(function ($route) {
        $route->get('countries', 'getCountries');
        $route->get('regions/{id}', 'getRegionsByCountry');
        $route->get('privacy', 'privacyPolicy');
        $route->get('policies', 'policyTerms');
        $route->get('view-policy/{key}', 'viewPolicyTerms');
        $route->get('contact-info', 'contactInfo');
    });
    // Location endpoints (Public - auto-complete and location management)
    Route::controller(LocationController::class)->prefix('location')->group(function ($route) {
        $route->get('current', 'getCurrentLocation');
        $route->post('set', 'setLocation');
        $route->get('cities', 'getCities');
        $route->get('regions', 'getRegions');
        $route->get('regions/{region}/cities', 'getCitiesByRegion');
    });
    Route::middleware(['auth:api', 'validate.device', 'profile.complete'])->group(function ($route) {
        $route->middleware('role:company')->group(function ($route) {
            $route->controller(CompanyProfileController::class)->prefix('company-profile')->group(function ($r) {
                $r->get('show', 'show')->middleware('job.profile.company');
                $r->post('update', 'update');
                $r->delete('logo', 'deleteLogo');
                $r->delete('cover', 'deleteCover');
                $r->post('add-gallery', 'addGalleryImage');
                $r->delete('delete-gallery/{index}', 'removeGalleryImage');
                $r->get('team', 'teamIndex')->middleware('job.profile.company');
                $r->post('add-team', 'teamStore');
                $r->post('update-team/{id}', 'teamUpdate');
                $r->delete('delete-team/{id}', 'teamDestroy');
            });
        });
    });
});
