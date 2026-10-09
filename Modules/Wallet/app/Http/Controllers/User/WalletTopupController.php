<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Contracts\PaymentInitiatorInterface;
use Modules\Wallet\Http\Requests\User\InitiateTopupRequest;
use Modules\Wallet\Http\Resources\User\InvoiceResource;

/**
 * User-facing endpoint for initiating a wallet top-up.
 *
 * The top-up is just one `kind` of Invoice (kind='wallet_topup'). The
 * controller calls the generic PaymentInitiator contract; the rail is
 * resolved based on the optional `preferred_rail` request param —
 * Stripe (card) by default, NowPayments (crypto) if the frontend
 * specifies it.
 *
 * The actual wallet credit happens later via the rail's webhook
 * (StripeWebhookController for card payments, NowPaymentsWebhookController
 * for crypto). This endpoint only creates the Invoice + hosted-checkout
 * URL and returns the URL the frontend should redirect/open.
 */
class WalletTopupController extends Controller
{
    public function __construct(
        protected PaymentInitiatorInterface $payments,
    ) {
    }

    /**
     * POST /api/v1/wallet/topup
     *
     * Body:
     *   { "amount": "50.00" }                         — defaults to Stripe (card)
     *   { "amount": "50.00", "preferred_rail": "nowpayments" } — crypto via NowPayments
     *
     * Returns: the new Invoice (kind=wallet_topup, status=PENDING) plus
     * the rail-hosted checkout URL the frontend should redirect/open.
     */
    public function initiate(InitiateTopupRequest $request)
    {
        try {
            $validated     = $request->validated();
            $userId        = Auth::id();
            $amount        = (string) $validated['amount'];
            $currency      = strtoupper((string) config('services.stripe.topup_currency', 'usd'));
            $preferredRail = $validated['preferred_rail'] ?? null;

            $invoice = $this->payments->initiate(
                kind:           'wallet_topup',
                buyerUserId:    $userId,
                sellerUserId:   null,        // user→platform; not a marketplace transaction
                amount:         $amount,
                currency:       $currency,
                referenceable:  null,        // Invoice is its own source of truth for top-ups
                metadata:       ['source' => 'web'],
                preferredRail:  $preferredRail,
            );

            return $this->successResponse(
                __('wallet::messages.topup_initiated'),
                [
                    'intent'       => new InvoiceResource($invoice),
                    'checkout_url' => $invoice->checkout_url,
                ],
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
