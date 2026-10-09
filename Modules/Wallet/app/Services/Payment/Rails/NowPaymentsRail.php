<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Illuminate\Http\Client\RequestException;
use Modules\Wallet\Contracts\PaymentRailInterface;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Exceptions\PaymentInitiationException;
use Modules\Wallet\Exceptions\RefundNotSupportedException;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Services\Payment\InvoiceContext;

/**
 * NowPayments rail — crypto user→platform payments via the hosted-checkout
 * flow (POST /v1/invoice → user redirected to invoice_url → user pays in
 * crypto → NowPayments converts to our settlement currency → IPN fires).
 *
 * Scope (v1):
 *   - User → platform invoices only (wallet_topup, ad_package, subscription
 *     renewal, etc.). Marketplace flow is unsupported and supports() refuses
 *     it; that path stays on StripeConnectRail.
 *   - Explicit-choice rail: supports() refuses unless the caller passes
 *     preferredRail='nowpayments'. Same pattern as PlatformCreditRail —
 *     never the silent default, frontend or caller must opt in.
 *
 * Settlement happens on NowPayments' side (configured in their dashboard).
 * We don't touch the crypto at any point — the bridge directive holds.
 *
 * Refunds: NowPayments has no automated refund API. refund() throws
 * RefundNotSupportedException; admin tooling handles manual off-rail
 * refunds (separate PR — see docs/nowpayments-implementation-plan.md §6).
 */
class NowPaymentsRail implements PaymentRailInterface
{
    public function __construct(
        private readonly NowPaymentsClient $client,
        private readonly string $successUrl,
        private readonly string $cancelUrl,
        private readonly string $partiallyPaidUrl,
        private readonly string $ipnCallbackUrl,
    ) {
    }

    public function name(): PaymentRail
    {
        return PaymentRail::NowPayments;
    }

    /**
     * Explicit-choice-only. Refuses marketplace (no seller-split support
     * via NowPayments) and refuses anything that didn't explicitly request
     * us — fallback resolution would otherwise pick crypto for fiat
     * top-ups, which would surprise users.
     */
    public function supports(InvoiceContext $ctx): bool
    {
        if ($ctx->isMarketplace()) {
            return false;
        }

        return $ctx->preferredRail === PaymentRail::NowPayments->value;
    }

    /**
     * Create the NowPayments hosted invoice and persist the rail-attached
     * fields on the Invoice. Status stays Pending — IPN drives the
     * Completed / Failed / Expired transitions (see NowPaymentsWebhookHandler).
     */
    public function initiate(Invoice $invoice): Invoice
    {
        $params = [
            'price_amount'        => (float) $invoice->amount,
            'price_currency'      => strtolower($invoice->currency),
            // pay_currency intentionally omitted — user picks crypto on the
            // hosted page. Pre-selecting locks them in and lowers conversion.
            'order_id'            => sprintf('INV-%d', $invoice->id),
            'order_description'   => sprintf('%s #%d', $invoice->kind, $invoice->id),
            'ipn_callback_url'    => $this->ipnCallbackUrl,
            'success_url'         => $this->successUrl,
            'cancel_url'          => $this->cancelUrl,
            'partially_paid_url'  => $this->partiallyPaidUrl,
            // is_fixed_rate=true: 10-minute window with the exchange rate
            // locked. Predictable settlement; the trade-off is slower users
            // see their invoice expire and need to re-initiate. Matches
            // typical crypto-checkout UX expectations.
            'is_fixed_rate'       => true,
            // is_fee_paid_by_user=true: NowPayments' processing/network fees
            // come off the user's payment, not our settlement. Keeps the
            // platform's per-transaction economics clean and aligns with
            // the bridge directive (we're a passthrough, not a fee absorber).
            'is_fee_paid_by_user' => true,
        ];

        try {
            $response = $this->client->createInvoice($params);
        } catch (RequestException $e) {
            throw new PaymentInitiationException(
                'NowPayments rejected the invoice creation: '
                    . ((string) ($e->response?->body() ?: $e->getMessage())),
                previous: $e,
            );
        }

        // Defensive — NowPayments' API has been stable but a missing field
        // here would leave the Invoice unusable (no checkout_url, no
        // partner_reference_id) and the user with nowhere to pay.
        if (empty($response['id']) || empty($response['invoice_url'])) {
            throw new PaymentInitiationException(
                'NowPayments returned an invalid response: missing id or invoice_url.',
            );
        }

        $invoice->update([
            'partner_reference_id' => (string) $response['id'],
            'checkout_url'         => (string) $response['invoice_url'],
            'metadata'             => array_merge($invoice->metadata ?? [], [
                'gateway'                => 'nowpayments',
                'nowpayments_invoice_id' => (string) $response['id'],
            ]),
        ]);

        return $invoice->fresh();
    }

    /**
     * NowPayments has no automated refund API — crypto refunds must be
     * issued MANUALLY from the merchant payout wallet on-chain. Callers
     * should catch RefundNotSupportedException and route to the admin
     * manual-refund flow (see docs/nowpayments-implementation-plan.md §6).
     *
     * @throws RefundNotSupportedException always
     */
    public function refund(Invoice $invoice, string $amount, string $reason): InvoiceRefund
    {
        throw new RefundNotSupportedException(
            'Crypto refunds for NowPayments must be issued manually from the '
                . 'payout wallet. Use the admin manual-refund endpoint to record '
                . 'an off-rail refund against invoice #' . $invoice->id . '.',
        );
    }
}
