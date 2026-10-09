<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\User\ProductController;
use Modules\Store\Http\Controllers\User\ProductCompareController;
use Modules\Store\Http\Controllers\User\PackageController;
use Modules\Store\Http\Controllers\User\StoreController;
use Modules\Store\Http\Controllers\User\CartController;
use Modules\Store\Http\Controllers\User\OrderController;
use Modules\Store\Http\Controllers\User\ReviewController;
use Modules\Store\Http\Controllers\User\UserAddressController;
use Modules\Store\Http\Controllers\User\ViolationController;
use Modules\Store\Http\Controllers\User\WishlistController;
use Modules\Store\Http\Controllers\User\TicketController;

Route::controller(ProductController::class)->prefix('products')->group(function ($route) {
    $route->get('', 'getProducts');
    $route->get('search', 'search');
    $route->get('details/{id}', 'show');
    $route->get('index', 'indexAllProducts');
});
Route::controller(ProductCompareController::class)->prefix('products')->group(function ($route) {
    $route->get('compare', 'compare');
});
Route::controller(PackageController::class)->group(function ($route) {
    $route->get('products/{productId}/bundles', 'index');
    $route->get('stores/{storeId}/bundles', 'getByStore');
    $route->get('bundles/{id}', 'show');
});
Route::controller(StoreController::class)->prefix('stores')->group(function ($route) {
    $route->get('details/{id}', 'show');
    $route->get('categories/{id}', 'getCategories');
    $route->get('best-sellers/{id}', 'getBestSellers');
    $route->get('discounted-products/{id}', 'getDiscountedProducts');
    $route->get('suggested-products/{id}', 'getSuggestedProducts');
});

Route::middleware(['auth:api', 'validate.device', 'profile.complete'])->group(function ($route) {
    $route->controller(UserAddressController::class)->prefix('addresses')->group(function ($route) {
        $route->get('', 'getMyAddresses');
        $route->post('add', 'addAddress');
        $route->put('update/{addressId}', 'updateAddress');
        $route->delete('remove/{addressId}', 'removeAddress');
    });
    $route->controller(CartController::class)->prefix('cart')->group(function ($route) {
        $route->get('', 'getMyCart');
        $route->post('add-item', 'addItem');
        $route->post('add-package', 'addPackage');
        $route->post('update-item/{itemId}', 'updateItem');
        $route->delete('remove-item/{itemId}', 'removeItem');
        $route->delete('clear', 'clearCart');
    });
    $route->controller(OrderController::class)->prefix('orders')->group(function ($route) {
        $route->post('check-coupon', 'checkCoupon');
        $route->post('create', 'createOrder');
        $route->get('', 'getMyOrders');
        $route->get('by-store/{storeId}', 'getOrdersByStore');
        $route->get('details/{orderId}', 'getOrder');
    });
    $route->controller(ReviewController::class)->prefix('reviews')->group(function ($route) {
        $route->get('get-by-product/{productId}', 'getReviews');
        $route->post('create', 'createReview');
        $route->post('update/{id}', 'updateReview');
        $route->delete('delete/{id}', 'deleteReview');
    });
    $route->controller(WishlistController::class)->prefix('wishlists')->group(function ($route) {
        $route->post('toggle', 'toggle');
        $route->get('', 'getWishlistByUserId');
    });
    $route->controller(TicketController::class)->prefix('tickets')->group(function ($route) {
        $route->get('my', 'index');
        $route->post('create', 'store');
        $route->get('show/{ticketId}', 'show');
        $route->get('issue-types', 'getIssueTypes');
    });
    $route->controller(ViolationController::class)->prefix('violations')->group(function ($route) {
        $route->get('reasons', 'getReasons');
        $route->post('submit', 'submit');
    });
});
