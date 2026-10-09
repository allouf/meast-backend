<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'stripe' => [
        // .env keys follow the project's existing naming. If the team later
        // standardizes to STRIPE_KEY / STRIPE_SECRET (Laravel convention),
        // change these env() lookups in one place — call sites use
        // config('services.stripe.*') and won't need to change.
        'key'             => env('Stripe_Publishable_key'),
        'secret'          => env('Stripe_Secret_key'),
        // Webhook signing secret — populated when we register the webhook
        // endpoint in the Stripe dashboard (or via `stripe listen` for local).
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET'),
        // Default currency for top-ups. Matches wallet denomination unit.
        'topup_currency'  => env('WALLET_TOPUP_CURRENCY', 'usd'),
        // Min/max top-up amount in major units (USD), enforced server-side.
        'topup_min'       => (float) env('WALLET_TOPUP_MIN', 1),
        'topup_max'       => (float) env('WALLET_TOPUP_MAX', 1000),
        // Where Stripe redirects the user after a successful / cancelled checkout.
        // The frontend can override per request; these are sane defaults for testing.
        'success_url'     => env('STRIPE_SUCCESS_URL', env('APP_URL') . '/wallet?topup=success'),
        'cancel_url'      => env('STRIPE_CANCEL_URL',  env('APP_URL') . '/wallet?topup=cancel'),
    ],

    'transak' => [
        // Public API key — safe to expose in widget URLs / frontend.
        'public_key'   => env('TRANSAK_PUBLIC_KEY'),
        // Secret API key — used SERVER-SIDE ONLY for HMAC signing of widget
        // URL params and verification of incoming webhook signatures.
        // NEVER ship this to the frontend.
        'secret_key'   => env('TRANSAK_SECRET_KEY'),
        // Environment toggle: 'staging' (sandbox) or 'production'.
        // Drives which API + widget hosts we hit.
        'environment'  => env('TRANSAK_ENVIRONMENT', 'staging'),
        // API base URL (lookup endpoints: countries, currencies, quotes,
        // session creation). NOT the same host as the partner-auth API.
        'api_base'     => env(
            'TRANSAK_API_BASE',
            env('TRANSAK_ENVIRONMENT', 'staging') === 'production'
                ? 'https://api-gateway.transak.com'
                : 'https://api-gateway-stg.transak.com',
        ),
        // Auth API base URL — only used to refresh the partner access
        // token. Different host than `api_base`.
        'auth_api_base' => env(
            'TRANSAK_AUTH_API_BASE',
            env('TRANSAK_ENVIRONMENT', 'staging') === 'production'
                ? 'https://api.transak.com'
                : 'https://api-stg.transak.com',
        ),
        // Widget host — the user-facing iframe / popup loads from here.
        // Frontend opens widgetBase + '?signed-params...' after our backend
        // signs the URL.
        'widget_base'  => env(
            'TRANSAK_WIDGET_BASE',
            env('TRANSAK_ENVIRONMENT', 'staging') === 'production'
                ? 'https://global.transak.com'
                : 'https://global-stg.transak.com',
        ),
        // Default redirect URL after the widget completes / cancels.
        // Frontend may override per-request via the sign-url payload.
        'redirect_url' => env(
            'TRANSAK_REDIRECT_URL',
            env('APP_URL') . '/wallet?topup=transak',
        ),
        // Default fiat currency shown in the widget when frontend doesn't
        // pass one explicitly. USD is fine for sandbox testing.
        'default_fiat_currency' => env('TRANSAK_DEFAULT_FIAT_CURRENCY', 'USD'),
        // Partner Access Token (JWT, 7-day expiry). Optional — if unset,
        // TransakAuthService will fetch one from the partner-auth refresh
        // endpoint using public_key + secret_key, and cache it. The env
        // override only exists for debugging / first-time bootstrapping.
        'access_token'    => env('TRANSAK_ACCESS_TOKEN'),
        // Domain that will host the embedded widget. Required by Transak's
        // session API (referrerDomain inside widgetParams). Use the platform
        // domain — `meast.local` locally, the UAT/production hostname elsewhere.
        'referrer_domain' => env('TRANSAK_REFERRER_DOMAIN', 'meast.local'),
    ],

    'stripe_connect' => [
        // The Connect account flavour created when a seller onboards.
        //   express  — Stripe-hosted KYB + light branding (recommended for v1)
        //   standard — full Stripe dashboard for sellers
        //   custom   — we build everything ourselves
        'account_type' => env('STRIPE_CONNECT_ACCOUNT_TYPE', 'express'),

        // Where Stripe redirects the seller after they complete (or close)
        // onboarding. Frontend route on our platform.
        'onboarding_return_url'  => env(
            'STRIPE_CONNECT_RETURN_URL',
            env('APP_URL') . '/seller/stripe-onboarding/return',
        ),

        // Where Stripe redirects the seller if their Account Link expires
        // before they complete it (links expire after ~7 days). Frontend
        // re-mints a fresh link by re-calling our endpoint.
        'onboarding_refresh_url' => env(
            'STRIPE_CONNECT_REFRESH_URL',
            env('APP_URL') . '/seller/stripe-onboarding/refresh',
        ),

        // When refunding a Connect charge, also reverse the application
        // fee so the seller's payout is fully unwound and the platform's
        // cut is returned. False would let the platform keep its fee on
        // refunded transactions — uncommon and seller-unfriendly.
        'refund_application_fee' => filter_var(
            env('STRIPE_CONNECT_REFUND_APP_FEE', true),
            FILTER_VALIDATE_BOOLEAN,
        ),

        // Per-kind application fee table. Decimal: 0.07 = 7%.
        // Stripe takes the fee off the top and routes it to the platform
        // account; the rest goes to the seller's connected account.
        // Unknown kinds fall back to `default`. Add a row when a new
        // marketplace `kind` is introduced — code config, no migration.
        // STARTING values; finance / management owns the final numbers.
        'application_fees' => [
            'service_order'    => (float) env('FEE_SERVICE_ORDER',    0.07),
            'service_milestone'=> (float) env('FEE_SERVICE_MILESTONE',0.07),
            'product_order'    => (float) env('FEE_PRODUCT_ORDER',    0.05),
            'default'          => (float) env('FEE_DEFAULT_MARKETPLACE', 0.07),
        ],
    ],

    'nowpayments' => [
        // Server-side API key — used to call NowPayments REST endpoints
        // (create invoice, fetch status, etc.). Never expose to frontend.
        'api_key'       => env('NOWPAYMENTS_API_KEY'),

        // Public key — safe to expose. Used only if we ever embed the
        // NowPayments widget directly; the hosted-checkout flow we run
        // server-side doesn't need this, but we read it through here for
        // completeness so frontend can request it via a config endpoint.
        'public_key'    => env('NOWPAYMENTS_PUBLIC_KEY'),

        // IPN (Instant Payment Notification) shared secret. Used to verify
        // the HMAC-SHA512 signature NowPayments puts on every webhook
        // payload. NEVER ship to frontend; if it leaks, rotate immediately
        // in the NowPayments dashboard.
        'ipn_secret'    => env('NOWPAYMENTS_IPN_SECRET'),

        // Crypto wallet address that NowPayments settles funds into. Set
        // in their dashboard; we read it here so admin code can echo it
        // back in receipts / refund instructions. Not used in the payment
        // initiation call itself.
        'payout_wallet' => env('NOWPAYMENTS_PAYOUT_WALLET'),

        // API host. Default is production; sandbox is api-sandbox.nowpayments.io.
        // Toggling environment is a single env-var change.
        'api_base'      => env('NOWPAYMENTS_API_BASE', 'https://api.nowpayments.io/v1'),

        // Public URL NowPayments POSTs IPN events to. MUST be HTTPS in
        // production. Local dev needs an ngrok / localtunnel exposure
        // since NowPayments can't reach localhost. Register this URL in
        // the NowPayments dashboard AFTER deploying the webhook route —
        // otherwise NP will see 404s and may pause IPN delivery.
        'ipn_callback_url' => env('NOWPAYMENTS_WEBHOOK_URL'),

        // Where NowPayments' hosted payment page redirects the user after
        // success / cancel / partially-paid. Frontend may override per-
        // request; these are defaults so the rail works without overrides.
        // partially_paid is its own URL because per our strict policy
        // (Option A) we mark the Invoice FAILED on underpayment — the
        // frontend page at this URL is the place to tell the user the
        // transfer was insufficient and what their next step is.
        'success_url'        => env('NOWPAYMENTS_SUCCESS_URL',         env('APP_URL') . '/wallet?topup=success'),
        'cancel_url'         => env('NOWPAYMENTS_CANCEL_URL',          env('APP_URL') . '/wallet?topup=cancel'),
        'partially_paid_url' => env('NOWPAYMENTS_PARTIALLY_PAID_URL',  env('APP_URL') . '/wallet?topup=partially_paid'),

        // Which crypto currencies we'll let the user pay with. Order
        // matters — the first is displayed first in the widget's picker.
        // Trim or extend this list to reflect what we support; each entry
        // must be a valid NowPayments currency code (lowercase).
        // BTC, ETH, USDT-TRC20, USDT-ERC20, USDC covers ~80% of crypto-
        // native users without exposing exotic chains we haven't tested.
        'supported_currencies' => ['btc', 'eth', 'usdttrc20', 'usdterc20', 'usdc'],
    ],

];
