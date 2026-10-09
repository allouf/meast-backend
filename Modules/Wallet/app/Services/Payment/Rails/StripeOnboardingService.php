<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Modules\Wallet\Exceptions\PaymentInitiationException;
use Modules\Wallet\Models\SellerStripeAccount;
use Stripe\Account as StripeAccount;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Manages a seller's Stripe Connect account lifecycle.
 *
 * Three responsibilities:
 *   1. createForUser(User $seller) — create a Stripe Connect Express
 *      account if the user doesn't already have one, and persist a
 *      SellerStripeAccount row with default flags (everything false).
 *   2. createOnboardingLink(SellerStripeAccount) — generate a Stripe-hosted
 *      Account Link URL the seller is redirected to to complete KYB.
 *   3. syncFromStripe(SellerStripeAccount) — pull latest state from Stripe
 *      (used both on demand and from the account.updated webhook handler).
 *
 * Account ownership semantics: each platform user gets at most one
 * Connect account in v1 (unique constraint on user_id). If they need
 * more (multiple brands, etc.) we'll relax that constraint later.
 */
class StripeOnboardingService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly string $accountType,
        private readonly string $returnUrl,
        private readonly string $refreshUrl,
    ) {
    }

    /**
     * Get-or-create the SellerStripeAccount for the given user. The first
     * call creates a Stripe Connect account; subsequent calls return the
     * existing record without touching Stripe.
     *
     * @param  int  $userId
     * @return SellerStripeAccount
     *
     * @throws PaymentInitiationException If Stripe rejects account creation.
     */
    public function getOrCreateForUser(int $userId, ?string $email = null, ?string $country = null): SellerStripeAccount
    {
        // Already onboarding / onboarded — return existing record. Caller
        // can then mint a fresh Account Link URL via createOnboardingLink().
        $existing = SellerStripeAccount::query()->where('user_id', $userId)->first();
        if ($existing !== null) {
            return $existing;
        }

        try {
            // Create a Connect account on Stripe's side. We use Express,
            // which means Stripe hosts the entire KYB UX and gives us a
            // pre-built dashboard for the seller.
            //
            // capabilities: tells Stripe what we want this account to be
            //   able to do. card_payments + transfers is the standard
            //   destination-charge configuration for marketplaces.
            $params = [
                'type' => $this->accountType,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers'     => ['requested' => true],
                ],
                'metadata' => [
                    'user_id' => (string) $userId,
                ],
            ];

            // Optional pre-fills. Only include if non-empty — Stripe rejects
            // empty-string values for these fields with "cannot be unset".
            if ($email !== null && $email !== '') {
                $params['email'] = $email;
            }
            if ($country !== null && $country !== '') {
                $params['country'] = $country;
            }

            $stripeAccount = $this->stripe->accounts->create($params);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Failed to create Stripe Connect account: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return SellerStripeAccount::create([
            'user_id'           => $userId,
            'stripe_account_id' => $stripeAccount->id,
            'account_type'      => $this->accountType,
            'country'           => $stripeAccount->country ?? null,
            'default_currency'  => $stripeAccount->default_currency ?? null,
            // Default flags from Stripe at creation time. Usually all false
            // until the seller completes KYB — but we read what's there
            // in case Stripe pre-filled something for an established business.
            'details_submitted' => (bool) ($stripeAccount->details_submitted ?? false),
            'charges_enabled'   => (bool) ($stripeAccount->charges_enabled ?? false),
            'payouts_enabled'   => (bool) ($stripeAccount->payouts_enabled ?? false),
            'last_synced_at'    => now(),
        ]);
    }

    /**
     * Generate a fresh Stripe Account Link URL for the seller to complete
     * (or update) their KYB. The link expires in ~7 days; if the seller
     * doesn't finish in time, mint a new one by calling this again.
     */
    public function createOnboardingLink(SellerStripeAccount $account): string
    {
        try {
            $link = $this->stripe->accountLinks->create([
                'account'     => $account->stripe_account_id,
                'refresh_url' => $this->refreshUrl,
                'return_url'  => $this->returnUrl,
                'type'        => 'account_onboarding',
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Failed to create Stripe Connect onboarding link: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return $link->url;
    }

    /**
     * Refresh our local SellerStripeAccount row from Stripe's current state.
     *
     * Called from two places:
     *   - account.updated webhook handler (push-driven sync).
     *   - GET /api/v1/seller/stripe-onboarding/status (pull-driven sync,
     *     useful when the seller returns from onboarding and we want
     *     fresh state without waiting for the webhook).
     */
    public function syncFromStripe(SellerStripeAccount $account): SellerStripeAccount
    {
        try {
            $stripeAccount = $this->stripe->accounts->retrieve($account->stripe_account_id);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Failed to fetch Stripe Connect account state: ' . $e->getMessage(),
                previous: $e,
            );
        }

        $this->applyStripePayload($account, $stripeAccount);
        return $account->fresh();
    }

    /**
     * Apply a Stripe Account payload to the local row. Used by both
     * syncFromStripe (pull) and the account.updated webhook handler (push).
     * Idempotent — safe to call repeatedly with the same payload.
     */
    public function applyStripePayload(SellerStripeAccount $account, StripeAccount $stripeAccount): void
    {
        $account->update([
            'details_submitted' => (bool) ($stripeAccount->details_submitted ?? false),
            'charges_enabled'   => (bool) ($stripeAccount->charges_enabled ?? false),
            'payouts_enabled'   => (bool) ($stripeAccount->payouts_enabled ?? false),
            'country'           => $stripeAccount->country ?? $account->country,
            'default_currency'  => $stripeAccount->default_currency ?? $account->default_currency,
            'last_synced_at'    => now(),
        ]);
    }
}
