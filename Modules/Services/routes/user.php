<?php

use Illuminate\Support\Facades\Route;
use Modules\Services\Http\Controllers\Provider\DisputeController;
use Modules\Services\Http\Controllers\User\CustomRequestCategoryController;
use Modules\Services\Http\Controllers\User\CustomRequestController;
use Modules\Services\Http\Controllers\User\CustomRequestOrderController;
use Modules\Services\Http\Controllers\User\CustomRequestQuoteController;
use Modules\Services\Http\Controllers\User\DisputeReasonController;
use Modules\Services\Http\Controllers\User\ProviderProfileController;
use Modules\Services\Http\Controllers\User\ServiceCategoryController;
use Modules\Services\Http\Controllers\User\ServiceListingController;
use Modules\Services\Http\Controllers\User\ServiceOfferOrderController;
use Modules\Services\Http\Controllers\User\ServiceOfferOrderPhaseController;
use Modules\Services\Http\Controllers\User\ServiceOfferOrderRevisionController;

Route::controller(ServiceListingController::class)->prefix('services')->group(function ($r) {
    $r->get('', 'index');
    $r->get('show/{id}', 'show');
    $r->get('{serviceId}/projects', 'projects');
    $r->post('{serviceId}/report', 'report')->middleware('auth:api');
    $r->post('{serviceId}/review', 'review')->middleware('auth:api');
});

Route::controller(ProviderProfileController::class)->prefix('providers')->group(function ($r) {
    $r->get('{id}/about', 'about');
    $r->get('{id}/services', 'services');
    $r->get('{id}/portfolio', 'portfolio');
});

Route::controller(ServiceCategoryController::class)->prefix('user/service-categories')->group(function ($r) {
    $r->get('', 'index');
});

Route::controller(CustomRequestCategoryController::class)->prefix('custom-request-categories')->group(function ($r) {
    $r->get('', 'index');
});

Route::middleware(['auth:api', 'validate.device'])->group(function () {

    Route::controller(CustomRequestController::class)->prefix('custom-requests')->group(function ($r) {
        $r->get('', 'index');
        $r->post('', 'store');
        $r->get('show/{id}', 'show');
        $r->post('cancel/{id}', 'cancel');
        $r->post('update/{id}', 'update');
        $r->get('{customRequest}/quotes', 'quotes')->whereNumber('customRequest');
    });

    Route::controller(CustomRequestQuoteController::class)->prefix('custom-requests')->group(function ($r) {
        $r->post('quotes/{quote}/accept', 'accept')->whereNumber('quote');
    });

    Route::controller(CustomRequestOrderController::class)->prefix('custom-request-orders')->group(function ($r) {
        $r->get('show/{id}', 'show');
        $r->post('{id}/process-payment', 'processPayment');
    });

    Route::controller(ServiceOfferOrderController::class)->prefix('service-orders')->group(function ($r) {
        $r->post('', 'store');
        $r->get('', 'index');
        $r->get('show/{id}', 'show');
        $r->post('cancel/{id}', 'cancel');
        // Buyer confirms delivery of a single-offer order. Releases the
        // escrow to the seller and transitions the order to Completed.
        // Mirrors MilestoneOrderPhaseService::acceptPhase semantics —
        // milestone phases already had this; single-offer orders didn't.
        $r->post('accept/{id}', 'accept');
        $r->post('approve-extension/{extensionId}', 'approveExtension');
        $r->post('reject-extension/{extensionId}', 'rejectExtension');
    });

    Route::controller(ServiceOfferOrderPhaseController::class)->prefix('service-orders')->group(function ($r) {
        $r->post('{order}/phases/{phase}/pay', 'pay')->whereNumber(['order', 'phase']);
        $r->post('{order}/phases/{phase}/accept', 'accept')->whereNumber(['order', 'phase']);
        $r->post('{order}/phases/{phase}/revisions', 'requestRevision')->whereNumber(['order', 'phase']);
    });

    Route::controller(ServiceOfferOrderRevisionController::class)->prefix('service-orders')->group(function ($r) {
        $r->get('{order}/revisions', 'index')->whereNumber('order');
        $r->post('{order}/revisions', 'store')->whereNumber('order');
    });

    Route::controller(DisputeController::class)->prefix('orders-disputes')->group(function ($r) {
        $r->post('orders/{order}', 'store');
        $r->get('', 'myDisputes');
        $r->get('{id}', 'show')->whereNumber('id');
    });

    Route::controller(DisputeReasonController::class)
        ->prefix('orders-reasons')
        ->group(function ($route) {
            $route->get('reasons', 'index');
        });
});
