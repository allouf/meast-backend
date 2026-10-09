<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\User\InvoiceLookupController;
use Modules\Wallet\Http\Controllers\User\SellerStripeOnboardingController;
use Modules\Wallet\Http\Controllers\User\TransakLookupController;
use Modules\Wallet\Http\Controllers\User\TransakQuoteController;
use Modules\Wallet\Http\Controllers\User\TransakSignController;
use Modules\Wallet\Http\Controllers\User\WalletController;
use Modules\Wallet\Http\Controllers\User\WalletTopupController;

/*
|--------------------------------------------------------------------------
| Wallet — User-facing routes
|--------------------------------------------------------------------------
|
| Mounted under /api/v1 by Wallet's RouteServiceProvider.
| All routes require an authenticated user (auth:api JWT) — wallets are
| strictly per-user, no anonymous access.
|
*/

Route::middleware(['auth:api'])->group(function () {
    Route::controller(WalletController::class)->prefix('wallet')->group(function ($route) {
        $route->get('', 'show');
        $route->get('transactions', 'transactions');
    });

    Route::controller(WalletTopupController::class)->prefix('wallet')->group(function ($route) {
        $route->post('topup', 'initiate');
    });

    // Invoice lookup by Stripe Checkout session id. Used by the
    // /checkout/return page on the frontend to know which order /
    // phase / topup the just-paid session belongs to.
    Route::get('invoices/by-session/{sessionId}', [InvoiceLookupController::class, 'bySession']);

    // Transak — non-custodial bridge for users to buy crypto via card.
    // Path A (hosted widget): we sign the URL, frontend opens widget,
    // Transak handles KYC + payment; we never see card data.
    Route::prefix('transak')->group(function () {
        Route::get('countries',         [TransakLookupController::class, 'countries']);
        Route::get('crypto-currencies', [TransakLookupController::class, 'cryptoCurrencies']);
        Route::get('fiat-currencies',   [TransakLookupController::class, 'fiatCurrencies']);
        Route::get('quote',             [TransakQuoteController::class, 'quote']);
        Route::post('sign-url',         [TransakSignController::class, 'signUrl']);
    });

    // Stripe Connect — seller-side onboarding endpoints. The seller calls
    // these to create / refresh their Connect account and check its KYB
    // state. Marketplace charges (StripeConnectRail) require a ready
    // SellerStripeAccount before they can be initiated.
    Route::controller(SellerStripeOnboardingController::class)
        ->prefix('seller/stripe-onboarding')
        ->group(function ($route) {
            $route->post('',       'start');
            $route->get('status',  'status');
        });
});
