<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\Seller\StoreController;
use Modules\Store\Http\Controllers\Seller\PolicyController;
use Modules\Store\Http\Controllers\Seller\PackageController;
use Modules\Store\Http\Controllers\Seller\ProductController;
use Modules\Store\Http\Controllers\Seller\CategoryController;
use Modules\Store\Http\Controllers\Seller\FlashSaleController;
use Modules\Store\Http\Controllers\Seller\InventoryController;
use Modules\Store\Http\Controllers\Seller\CouponController;
use Modules\Store\Http\Controllers\Seller\InvitationController;
use Modules\Store\Http\Controllers\Seller\SlaRuleController;
use Modules\Store\Http\Controllers\Seller\StoreMemberController;
use Modules\Store\Http\Controllers\Seller\TicketController;
use Modules\Store\Http\Controllers\Seller\OrderController;
use Modules\Store\Http\Controllers\Seller\ReviewController;
use Modules\Store\Http\Controllers\Seller\Statistics\OrderStatisticsController;
use Modules\Store\Http\Controllers\Seller\Statistics\ReviewStatisticsController;

Route::controller(CategoryController::class)->prefix('categories')->group(function ($route) {
    $route->get('', 'getCategories');
    $route->get('get-with-childrens', 'getWithChildrens');
    $route->get('childrens/{id}', 'getChildrens');
});

Route::get('stores/invitations/preview/{token}', [InvitationController::class, 'getInvitationPreview']);

Route::middleware(['auth:api', 'profile.complete'])->group(function ($route) {
    $route->controller(StoreController::class)->prefix('stores')->group(function ($route) {
        $route->get('get-by-user', 'getByUser');
        $route->get('get-deleted-by-user', 'getDeletedStoresByUser');
        $route->post('create', 'create')->middleware('throttle:10,1');
        $route->get('show/{id}', 'show');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'delete');
        $route->post('restore/{id}', 'restore');
        $route->delete('force-delete/{id}', 'forceDelete');
        $route->post('switch-activation/{id}', 'switchActivation');
        $route->get('categories/{storeId}', 'getCategories');
        $route->delete('delete-image/{storeId}', 'deleteImage');
        $route->post('uploade-image/{storeId}', 'uploadImage');
    });

    $route->controller(InvitationController::class)->prefix('stores/invitations')->group(function ($route) {
        $route->post('send', 'sendInvitation')->middleware('throttle:10,1');
        $route->post('resend', 'resendInvitation')->middleware('throttle:10,1');
        $route->post('accept/{token}', 'acceptInvitation')->middleware('throttle:10,1');
        $route->post('revoke/{memberId}', 'revoke');
    });

    $route->controller(StoreMemberController::class)->prefix('stores/members')->group(function ($route) {
        $route->get('/{storeId}', 'getStoreMembers');
        $route->get('show/{memberId}', 'show');
        $route->post('update-role/{memberId}/', 'updateRole');
        $route->delete('remove/{memberId}/', 'remove');
        $route->post('restore/{member}', 'restore');
        $route->get('members-removed/{storeId}', 'getRemovedMembers');
    });

    $route->controller(PolicyController::class)->prefix('policies')->group(function ($route) {
        $route->get('', 'getPolicy');
        $route->post('update-policy', 'updateStorePolicy');
    });

    $route->controller(ProductController::class)->prefix('products')->group(function ($route) {
        $route->get('get-by-store/with-variants/{storeId}', 'getByStoreWithVariants');
        $route->get('get-by-store/{storeId}', 'getByStore');
        $route->get('related-products/{id}', 'getRelatedProducts');
        $route->get('show/{id}', 'show');
        $route->post('create', 'create');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'delete');
        $route->post('switch-activation/{id}', 'switchActivation');
        $route->post('duplicate/{id}', 'duplicate');
        $route->delete('gallery/{galleryItemId}', 'deleteGalleryItem');
        $route->post('gallery/sort/{productId}', 'updateGallerySort');
        $route->post('variants/update/{variantId}', 'updateVariant');
        $route->get('variants/{id}', 'getProductVariants');
    });

    $route->controller(PackageController::class)->prefix('packages')->group(function ($route) {
        $route->get('product/{productId}/list', 'index');
        $route->get('store/{storeId}/list', 'getByStore');
        $route->get('details/{id}', 'show');
        $route->post('product/{productId}/create', 'store');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'destroy');
        $route->post('add-items/{id}', 'attachItems');
        $route->post('remove-items/{id}', 'detachItems');
        $route->patch('toggle-activation/{packageId}', 'toggleActivation');
    });

    $route->controller(FlashSaleController::class)->prefix('flash-sales')->group(function ($route) {
        $route->get('by-store/{id}', 'getByStore');
        $route->post('create', 'store');
        $route->get('flash-sale/{id}', 'show');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'destroy');
    });

    $route->controller(InventoryController::class)->prefix('inventory')->group(function ($route) {
        $route->get('get-by-store/{id}', 'getVariantsByStore');
        $route->get('export', 'exportProductVariants');
        $route->post('import', 'importVariants');
    });

    $route->controller(CouponController::class)->prefix('coupons')->group(function ($route) {
        $route->get('get-by-store/{storeId}', 'indexByStore');
        $route->get('show/{couponId}', 'show');
        $route->post('create', 'store');
        $route->delete('delete/{id}', 'destroy');
    });

    $route->controller(SlaRuleController::class)->prefix('sla-rules')->group(function ($route) {
        $route->get('{storeId}', 'index');
        $route->get('show/{id}', 'show');
        $route->post('create', 'store');
        $route->delete('delete/{id}', 'destroy');
    });

    $route->controller(TicketController::class)->prefix('support-tickets')->group(function ($route) {
        $route->get('get-by-store/{storeId}', 'index');
        $route->post('reply/{ticketId}', 'reply');
        $route->post('resolve/{ticketId}', 'resolve');
        $route->post('escalate/{ticketId}', 'escalate');
        $route->get('show/{ticketId}', 'show');
        $route->get('issue-types', 'getIssueTypes');
        $route->post('review-return/{ticketId}', 'reviewReturn');
        $route->post('decide-return/{ticketId}', 'decideReturn');
        $route->post('process-refund/{ticketId}', 'processRefund');
        $route->post('replacement-sent/{ticketId}', 'markReplacementSent');
    });

    $route->controller(OrderController::class)->prefix('orders')->group(function ($route) {
        $route->get('get-by-store/{storeId}', 'index');
        $route->get('show/{orderId}', 'show');
        $route->post('update-status/{orderId}', 'updateStatus');
    });

    $route->controller(ReviewController::class)->prefix('reviews')->group(function ($route) {
        $route->get('get-by-store/{storeId}', 'getReviews');
        $route->get('show/{reviewId}', 'show');
        $route->post('approve/{reviewId}', 'approve');
    });

    $route->prefix('statistics')->group(function ($route) {
        $route->controller(OrderStatisticsController::class)->prefix('orders')->group(function ($route) {
            $route->get('by-store/{storeId}', 'getOrderStatistics');
        });
        $route->controller(ReviewStatisticsController::class)->prefix('reviews')->group(function ($route) {
            $route->get('by-store/{storeId}', 'getReviewStatistics');
        });
    });
});
