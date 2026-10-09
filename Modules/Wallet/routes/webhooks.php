<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\Webhooks\NowPaymentsWebhookController;
use Modules\Wallet\Http\Controllers\Webhooks\StripeWebhookController;
use Modules\Wallet\Http\Controllers\Webhooks\TransakWebhookController;

/*
|--------------------------------------------------------------------------
| Wallet — Webhook routes (no authentication)
|--------------------------------------------------------------------------
|
| Mounted under /api/v1/wallet/webhooks by Wallet's RouteServiceProvider.
|
| These endpoints are public — they accept incoming POSTs from external
| services (currently: Stripe). Authentication is by request signature
| (Stripe-Signature header), verified inside the controller via the
| webhook signing secret in services.stripe.webhook_secret.
|
| Do NOT add auth:api middleware here. Stripe doesn't have credentials.
|
*/

Route::post('stripe',      [StripeWebhookController::class,      'handle']);
Route::post('transak',     [TransakWebhookController::class,     'handle']);
Route::post('nowpayments', [NowPaymentsWebhookController::class, 'handle']);
