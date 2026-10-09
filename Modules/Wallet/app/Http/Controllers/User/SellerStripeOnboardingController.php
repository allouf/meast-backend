<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Models\SellerStripeAccount;
use Modules\Wallet\Services\Payment\Rails\StripeOnboardingService;

/**
 * Endpoints for sellers to onboard with (and check status of) their Stripe
 * Connect account. Required prerequisite for receiving marketplace payments
 * via the StripeConnectRail.
 *
 * Flow:
 *   1. Seller clicks "Set up payments" on their dashboard.
 *   2. Frontend POSTs /seller/stripe-onboarding → we return an Account
 *      Link URL.
 *   3. Frontend redirects the seller to that URL (Stripe-hosted KYB).
 *   4. After completion (or close), Stripe redirects back to our
 *      configured return_url; frontend then GETs /status to read the
 *      latest state from our DB.
 *   5. Asynchronously, Stripe also fires account.updated webhooks that
 *      keep our SellerStripeAccount row in sync independent of step 4.
 */
class SellerStripeOnboardingController extends Controller
{
    public function __construct(
        protected StripeOnboardingService $onboarding,
    ) {
    }

    /**
     * POST /api/v1/seller/stripe-onboarding
     *
     * Idempotent: if the seller already has a Stripe Connect account,
     * we just mint a fresh Account Link without creating a duplicate.
     * Frontend can call this repeatedly (e.g. after the seller's old
     * link expired without them completing).
     *
     * Returns: { account: SellerStripeAccount summary, onboarding_url }
     */
    public function start()
    {
        try {
            /** @var \Modules\Core\Models\User $user */
            $user = Auth::user();

            $account = $this->onboarding->getOrCreateForUser(
                userId: $user->id,
                email:  $user->email ?? null,
            );

            $onboardingUrl = $this->onboarding->createOnboardingLink($account);

            return $this->successResponse(
                __('wallet::messages.stripe_onboarding_link_created'),
                [
                    'account'         => $this->summarize($account->fresh()),
                    'onboarding_url'  => $onboardingUrl,
                ],
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * GET /api/v1/seller/stripe-onboarding/status
     *
     * Returns the seller's local SellerStripeAccount summary. Optionally
     * forces a fresh sync from Stripe (?refresh=1) — useful right after
     * the seller returns from onboarding and we don't want to wait for
     * the webhook to fire.
     */
    public function status()
    {
        try {
            $userId = Auth::id();

            $account = SellerStripeAccount::query()->where('user_id', $userId)->first();
            if ($account === null) {
                return $this->errorResponse(
                    __('wallet::messages.stripe_onboarding_not_started'),
                    404,
                );
            }

            if (request()->boolean('refresh')) {
                $account = $this->onboarding->syncFromStripe($account);
            }

            return $this->successResponse(
                __('wallet::messages.fetch_success'),
                ['account' => $this->summarize($account)],
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Resource shape — kept inline because there's only one endpoint shape
     * and a separate Resource class would be overkill. Promote to a real
     * JsonResource if more endpoints start using it.
     */
    private function summarize(SellerStripeAccount $a): array
    {
        return [
            'stripe_account_id'   => $a->stripe_account_id,
            'details_submitted'   => $a->details_submitted,
            'charges_enabled'     => $a->charges_enabled,
            'payouts_enabled'     => $a->payouts_enabled,
            'ready_for_charges'   => $a->isReadyForCharges(),
            'country'             => $a->country,
            'default_currency'    => $a->default_currency,
            'last_synced_at'      => $a->last_synced_at?->toIso8601String(),
        ];
    }
}
