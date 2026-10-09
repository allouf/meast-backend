<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around NowPayments' REST API.
 *
 * NowPayments has no PHP SDK, so we hit endpoints directly via Laravel's
 * Http facade. Authentication uses the `x-api-key` header on every call.
 *
 * Current surface (minimum needed for the user→platform top-up flow):
 *
 *   - POST /invoice         — create a hosted-checkout payment session
 *   - GET  /payment/{id}    — poll the current status of a payment
 *
 * Errors (non-2xx responses) propagate as Illuminate\Http\Client\RequestException
 * via the `->throw()` chain. The rail layer catches them and re-wraps as
 * PaymentInitiationException so the controller / event layer sees a
 * consistent failure-mode contract across rails.
 *
 * Not bound as a Laravel singleton itself — WalletServiceProvider constructs
 * one in the same closure that builds NowPaymentsRail and passes it in
 * (mirrors how StripeClient is wired for the Stripe rails).
 */
class NowPaymentsClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiBase,
    ) {
    }

    /**
     * Create an invoice on NowPayments. Returns the parsed JSON response;
     * the keys we care about downstream are `id` (→ partner_reference_id)
     * and `invoice_url` (→ Invoice::checkout_url).
     *
     * Typical $params shape:
     *
     *   [
     *     'price_amount'      => 25.00,
     *     'price_currency'    => 'usd',
     *     'pay_currency'      => null,        // null = let user pick on hosted page
     *     'order_id'          => 'INV-7',
     *     'order_description' => 'MEAST wallet top-up — Invoice #7',
     *     'ipn_callback_url'  => 'https://uat.middleeastcommerce.net/...',
     *     'success_url'       => '...',
     *     'cancel_url'        => '...',
     *   ]
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws RequestException on non-2xx response
     */
    public function createInvoice(array $params): array
    {
        return $this->client()
            ->post('/invoice', $params)
            ->throw()
            ->json();
    }

    /**
     * Fetch the current state of a payment. Used by reconciliation cron
     * and admin tools, NOT the happy-path flow (which is webhook-driven).
     * Returns NowPayments' full payment object including `payment_status`,
     * `pay_amount`, `outcome_amount`, etc.
     *
     * @return array<string, mixed>
     *
     * @throws RequestException on non-2xx response
     */
    public function getPayment(string $paymentId): array
    {
        return $this->client()
            ->get('/payment/' . $paymentId)
            ->throw()
            ->json();
    }

    /**
     * One configured Http client per call. We don't share state; the
     * underlying Guzzle handler is cheap to construct. The 15-second
     * timeout is generous — NowPayments' p95 for /invoice is ~400ms.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl($this->apiBase)
            ->withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->timeout(15);
    }
}
