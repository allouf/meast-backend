<?php

namespace Modules\Wallet\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Wallet\Console\Commands\ExpireStalePendingInvoicesCommand;
use Modules\Wallet\Contracts\PaymentInitiatorInterface;
use Modules\Wallet\Repositories\Eloquent\WalletRepository;
use Modules\Wallet\Repositories\Interfaces\WalletRepositoryInterface;
use Modules\Wallet\Services\Payment\PaymentInitiator;
use Modules\Wallet\Services\Payment\Rails\NowPaymentsClient;
use Modules\Wallet\Services\Payment\Rails\NowPaymentsRail;
use Modules\Wallet\Services\Payment\Rails\NowPaymentsWebhookHandler;
use Modules\Wallet\Services\Payment\Rails\PlatformCreditRail;
use Modules\Wallet\Services\Payment\Rails\StripeConnectRail;
use Modules\Wallet\Services\Payment\Rails\StripeOnboardingService;
use Modules\Wallet\Services\Payment\Rails\StripeRail;
use Modules\Wallet\Services\Payment\Rails\StripeWebhookHandler;
use Modules\Wallet\Services\Payment\RailResolver;
use Modules\Wallet\Services\TransakAuthService;
use Modules\Wallet\Services\TransakLookupService;
use Modules\Wallet\Services\TransakSessionService;
use Modules\Wallet\Services\TransakSignatureService;
use Modules\Wallet\Services\WalletService;
use Stripe\StripeClient;

/**
 * Wallet module service provider.
 *
 * Mirrors Modules/Store/StoreServiceProvider's lean structure: registers
 * translations, loads migrations, binds the repository interface to its
 * Eloquent implementation, and delegates HTTP routing to the module's
 * RouteServiceProvider.
 *
 * The auto-generated nwidart scaffolding (registerCommands /
 * registerCommandSchedules / registerConfig / registerViews / Blade
 * component namespace) is intentionally absent — this module is
 * API-only and the team's other modules don't use any of it.
 */
class WalletServiceProvider extends ServiceProvider
{
    protected string $name = 'Wallet';

    protected string $nameLower = 'wallet';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerConsoleCommands();
    }

    /**
     * Register module console commands and schedule recurring jobs.
     *
     * Commands are registered only when running in CLI to avoid loading them
     * on every HTTP request. Scheduling is wired via callAfterResolving so
     * the module doesn't have to know about the framework's Schedule
     * lifecycle ahead of time.
     */
    protected function registerConsoleCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            ExpireStalePendingInvoicesCommand::class,
        ]);

        // Schedule the expiration sweep every 5 minutes. Five minutes ×
        // 500 default --limit = 6,000/hour ceiling, well above realistic
        // invoice creation rates. Adjust the cadence here if traffic grows.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('wallet:expire-stale-invoices')
                ->everyFiveMinutes()
                ->withoutOverlapping()      // a slow run won't pile up
                ->runInBackground()
                ->onOneServer();            // safe for multi-app-server deploys
        });
    }

    public function register(): void
    {
        $this->registerRepositories();
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Translations: prefer the published path (resources/lang/modules/wallet)
     * if it exists; otherwise fall back to the module's own lang/ directory.
     * Mirrors Modules/Store's resolver.
     */
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

    /**
     * Bind repository interfaces to their Eloquent implementations.
     */
    protected function registerRepositories(): void
    {
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);

        // ───────────── Payment abstraction (Track 4) ─────────────
        // The RailResolver is a singleton that holds registered rails. We
        // build it once here, register every available rail in the order
        // we want them attempted (more-specific first), and reuse it for
        // every PaymentInitiator call.
        $this->app->singleton(RailResolver::class, function ($app) {
            $resolver = new RailResolver();

            // PlatformCreditRail — instant debit from the buyer's wallet.
            // Registered FIRST so when caller passes preferredRail=
            // 'platform_credit' it wins resolution. (Order matters only for
            // the no-preference fallback path; this rail's supports() refuses
            // unless explicitly chosen, so it never runs as a silent default.)
            $resolver->register(new PlatformCreditRail(
                wallets: $app->make(WalletService::class),
            ));

            // StripeConnectRail — marketplace flow (buyer→seller via Stripe
            // Connect destination charges). Registered before StripeRail
            // so when seller_user_id is set, this wins resolution. (Order
            // is also a defence-in-depth: StripeRail's supports() refuses
            // marketplace flow anyway, but having the more-specific rail
            // earlier in the iteration order is the canonical pattern.)
            $resolver->register(new StripeConnectRail(
                stripe:               new StripeClient((string) config('services.stripe.secret')),
                successUrl:           (string) config('services.stripe.success_url'),
                cancelUrl:            (string) config('services.stripe.cancel_url'),
                applicationFees:      (array)  config('services.stripe_connect.application_fees', []),
                refundApplicationFee: (bool)   config('services.stripe_connect.refund_application_fee', true),
            ));

            // Stripe (non-Connect) — the user→platform card rail. Used by
            // wallet_topup, ad_package, subscription_renewal, etc.
            $resolver->register(new StripeRail(
                stripe:           new StripeClient((string) config('services.stripe.secret')),
                defaultCurrency:  (string) config('services.stripe.topup_currency', 'usd'),
                successUrl:       (string) config('services.stripe.success_url'),
                cancelUrl:        (string) config('services.stripe.cancel_url'),
            ));

            // NowPaymentsRail — crypto user→platform via hosted checkout.
            // Explicit-choice-only (supports() refuses unless preferredRail=
            // 'nowpayments'), so registration order doesn't matter here —
            // RailResolver looks up by name first. Appended last for clarity.
            $resolver->register(new NowPaymentsRail(
                client:           new NowPaymentsClient(
                    apiKey:  (string) config('services.nowpayments.api_key'),
                    apiBase: (string) config('services.nowpayments.api_base'),
                ),
                successUrl:       (string) config('services.nowpayments.success_url'),
                cancelUrl:        (string) config('services.nowpayments.cancel_url'),
                partiallyPaidUrl: (string) config('services.nowpayments.partially_paid_url'),
                ipnCallbackUrl:   (string) config('services.nowpayments.ipn_callback_url'),
            ));

            // Future rails register here:
            //   - TapRail / CheckoutComRail — alternative card processors
            //   - MoonPayRail — alternative crypto on-ramp

            return $resolver;
        });

        // PaymentInitiator implements the public contract every other
        // module calls. Bound to the interface so callers can type-hint
        // PaymentInitiatorInterface and stay testable / swappable.
        $this->app->singleton(
            PaymentInitiatorInterface::class,
            fn ($app) => new PaymentInitiator(
                resolver:      $app->make(RailResolver::class),
                walletService: $app->make(WalletService::class),
            ),
        );

        // Stripe Connect onboarding service — manages SellerStripeAccount
        // lifecycle. Needs its own StripeClient + onboarding URLs.
        $this->app->singleton(
            StripeOnboardingService::class,
            fn ($app) => new StripeOnboardingService(
                stripe:        new StripeClient((string) config('services.stripe.secret')),
                accountType:   (string) config('services.stripe_connect.account_type', 'express'),
                returnUrl:     (string) config('services.stripe_connect.onboarding_return_url'),
                refreshUrl:    (string) config('services.stripe_connect.onboarding_refresh_url'),
            ),
        );

        // Stripe inbound webhook handler. Needs the WalletService, the
        // webhook secret string, and the onboarding service (for
        // account.updated event syncing).
        $this->app->singleton(
            StripeWebhookHandler::class,
            fn ($app) => new StripeWebhookHandler(
                walletService: $app->make(WalletService::class),
                webhookSecret: (string) config('services.stripe.webhook_secret'),
                onboarding:    $app->make(StripeOnboardingService::class),
            ),
        );

        // NowPayments inbound IPN handler. Needs the WalletService for
        // wallet credits on `finished` events, and the IPN secret for
        // HMAC-SHA512 signature verification of incoming payloads.
        $this->app->singleton(
            NowPaymentsWebhookHandler::class,
            fn ($app) => new NowPaymentsWebhookHandler(
                walletService: $app->make(WalletService::class),
                ipnSecret:     (string) config('services.nowpayments.ipn_secret'),
            ),
        );

        // ───────────── Transak (token on-ramp bridge) ─────────────
        // Separate from the Payment abstraction above — Transak is for
        // users to BUY MEEC tokens, not for paying platform invoices.
        // Singletons because each holds keys + API base URLs that are
        // stable per request.
        $this->app->singleton(
            TransakSignatureService::class,
            fn () => new TransakSignatureService(
                (string) config('services.transak.secret_key'),
            ),
        );

        $this->app->singleton(
            TransakLookupService::class,
            fn () => new TransakLookupService(
                apiBase:   (string) config('services.transak.api_base'),
                publicKey: (string) config('services.transak.public_key'),
            ),
        );

        $this->app->singleton(
            TransakAuthService::class,
            fn () => new TransakAuthService(
                authApiBase:    (string) config('services.transak.auth_api_base'),
                apiKey:         (string) config('services.transak.public_key'),
                apiSecret:      (string) config('services.transak.secret_key'),
                manualOverride: config('services.transak.access_token') ?: null,
            ),
        );

        $this->app->singleton(
            TransakSessionService::class,
            fn ($app) => new TransakSessionService(
                apiBase:        (string) config('services.transak.api_base'),
                apiKey:         (string) config('services.transak.public_key'),
                referrerDomain: (string) config('services.transak.referrer_domain'),
                auth:           $app->make(TransakAuthService::class),
            ),
        );
    }
}
