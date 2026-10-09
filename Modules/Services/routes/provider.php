<?php

use Illuminate\Support\Facades\Route;
use Modules\Services\Http\Controllers\Admin\ServiceOrderRejectionReasonController;
use Modules\Services\Http\Controllers\Provider\CustomRequestDiscoverController;
use Modules\Services\Http\Controllers\Provider\CustomRequestQuoteController;
use Modules\Services\Http\Controllers\Provider\DisputeController;
use Modules\Services\Http\Controllers\Provider\DisputeReasonController;
use Modules\Services\Http\Controllers\Provider\ProviderProfileController;
use Modules\Services\Http\Controllers\Provider\ProviderProjectController;
use Modules\Services\Http\Controllers\Provider\ServiceCategoryController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferAvailableSlotController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferFaqController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferOrderPhaseController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferOrderController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferOrderExtensionReasonController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferOrderRevisionController;
use Modules\Services\Http\Controllers\Provider\ServiceOfferPlanController;
use Modules\Services\Http\Controllers\Provider\MilestoneController;
use Modules\Services\Http\Controllers\Provider\BoostCampaignController;
use Modules\Services\Http\Controllers\Provider\BoostPlanController;

Route::middleware(['auth:api', 'validate.device'])->group(function () {

    Route::controller(ProviderProfileController::class)->prefix('service-provider')->group(function () {
        Route::post('', 'store');
        Route::post('update', 'update');
        Route::post('submit-verification', 'submitVerification');
        Route::delete('remove', 'removeProfileField');
        Route::get('verification/{id}', 'verificationStatus')->whereNumber('id');

        Route::get('', 'show')
            ->middleware('service.profile.provider');
    });

    Route::controller(ServiceCategoryController::class)->prefix('service-categories')->group(function ($r) {
        $r->get('', 'index');
    });

    Route::middleware('service.profile.provider')->prefix('milestones')->group(function () {
        Route::controller(MilestoneController::class)->group(function ($r) {
            $r->get('', 'index');
            $r->get('show/{id}', 'show')->whereNumber('id');
            $r->post('', 'store');
            $r->post('update/{id}', 'update')->whereNumber('id');
            $r->delete('delete/{id}', 'destroy')->whereNumber('id');
            $r->post('toggle-activation/{id}', 'toggleActivation')->whereNumber('id');
            $r->post('{milestoneId}/services', 'storeServiceLine')->whereNumber('milestoneId');
            $r->post('services/update/{lineId}', 'updateServiceLine')->whereNumber('lineId');
            $r->delete('services/delete/{lineId}', 'destroyServiceLine')->whereNumber('lineId');
        });
    });

    Route::middleware('service.profile.provider')->prefix('service-offers')->group(function () {

        Route::controller(ServiceOfferController::class)->group(function ($r) {
            $r->get('stats', 'stats');
            $r->get('', 'index');
            $r->get('show/{id}', 'show');
            $r->post('', 'store')->middleware('service.category.verification');
            $r->post('update/{id}', 'update')->middleware('service.category.verification');
            $r->delete('delete/{id}', 'destroy');
        });

        Route::controller(ServiceOfferPlanController::class)->group(function ($r) {
            $r->post('{serviceId}/add-plan', 'store');
            $r->post('update-plan/{planId}', 'update');
            $r->delete('delete-plan/{planId}', 'destroy');
        });

        Route::controller(ServiceOfferFaqController::class)->group(function ($r) {
            $r->post('{serviceId}/add-faq', 'store');
            $r->post('update-faq/{faqId}', 'update');
            $r->delete('delete-faq/{faqId}', 'destroy');
        });
        Route::controller(ServiceOfferAvailableSlotController::class)->group(function ($r) {
            $r->post('{serviceId}/update-slots', 'updateAllSlots');
        });
    });

    Route::middleware('service.profile.provider')->group(function () {
        Route::controller(BoostCampaignController::class)->prefix('boost-campaigns')->group(function ($r) {
            $r->get('', 'index');
            $r->post('', 'store');
        });
    });

    Route::controller(ProviderProjectController::class)->prefix('provider-projects')->group(function ($r) {
        $r->get('', 'index');
        $r->get('show/{id}', 'show');
        $r->post('', 'store');
        $r->post('update/{id}', 'update');
        $r->delete('delete/{id}', 'destroy');
    });
    Route::middleware('service.profile.provider')->prefix('provider-orders')->group(function () {

        Route::controller(ServiceOfferOrderController::class)
            ->group(function ($r) {
                $r->get('', 'index');
                $r->get('show/{id}', 'show');
                $r->get('by-service/{serviceOfferId}', 'serviceOrders');
                $r->post('start/{id}', 'start');
                $r->post('reject/{id}', 'reject');
                $r->post('deliver/{id}', 'deliver');
                $r->post('cancel/{id}', 'cancel');
                $r->post('change-status/{id}', 'changeStatus');
                $r->post('request-extension/{id}', 'requestExtension');
                // Negotiation lifecycle — provider defines a milestone template
                // inline against a status=Negotiation order. Creates the
                // template + phases + flips status to PendingPayment so the
                // buyer can pay the first phase.
                $r->post('{id}/define-milestone', 'defineMilestone')->whereNumber('id');
            });

        Route::controller(ServiceOfferOrderPhaseController::class)->group(function ($r) {
            $r->post('{order}/phases/{phase}/start', 'start')->whereNumber(['order', 'phase']);
            $r->post('{order}/phases/{phase}/deliver', 'deliver')->whereNumber(['order', 'phase']);
        });

        Route::controller(ServiceOfferOrderRevisionController::class)->group(function ($r) {
            $r->get('{order}/revisions', 'index')->whereNumber('order');
            $r->post('revisions/{revision}/reject', 'reject')->whereNumber('revision');
            $r->post('revisions/{revision}/accept', 'accept')->whereNumber('revision');
        });
    });

    Route::middleware('service.profile.provider')->group(function () {
        Route::controller(CustomRequestDiscoverController::class)->prefix('discover/requests')->group(function ($r) {
            $r->get('', 'index');
            $r->get('{customRequest}', 'show')->whereNumber('customRequest');
        });

        Route::get('provider/quotes/stats', [CustomRequestQuoteController::class, 'stats']);
        Route::get('provider/quotes', [CustomRequestQuoteController::class, 'index']);
        Route::match(['put', 'patch'], 'quotes/{quote}', [CustomRequestQuoteController::class, 'update'])->whereNumber('quote');
        Route::patch('quotes/{quote}/cancel', [CustomRequestQuoteController::class, 'cancel'])->whereNumber('quote');

        Route::controller(CustomRequestQuoteController::class)->prefix('custom-requests')->group(function ($r) {
            $r->post('{customRequest}/quotes', 'store')->whereNumber('customRequest');
        });

        Route::controller(ServiceOrderRejectionReasonController::class)
            ->prefix('rejection-reasons')
            ->group(function ($route) {
                $route->get('active', 'listActive');
            });

        Route::controller(ServiceOfferOrderExtensionReasonController::class)
            ->prefix('extension-reasons')
            ->group(function ($route) {
                $route->get('active', 'index');
            });
    });

    Route::controller(DisputeController::class)->prefix('disputes')->group(function ($r) {
        $r->post('orders/{order}', 'store');
        $r->get('', 'myDisputes');
        $r->get('{id}', 'show')->whereNumber('id');
    });

    Route::controller(DisputeReasonController::class)
        ->prefix('dispute-reasons')
        ->group(function ($route) {
            $route->get('active', 'index');
        });

    Route::controller(BoostPlanController::class)->prefix('boost-plans')->group(function ($route) {
        $route->get('', 'index');
    });
});
