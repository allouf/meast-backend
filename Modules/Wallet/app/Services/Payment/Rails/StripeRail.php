<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Modules\Wallet\Contracts\PaymentRailInterface;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Enums\RefundReason;
use Modules\Wallet\Exceptions\PaymentInitiationException;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Services\Payment\InvoiceContext;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Stripe Checkout-based rail for direct (non-marketplace) card payments.
 *
 * Replaces the bulk of StripeTopupService::createCheckoutSession. Driven
 * by PaymentInitiator after the Invoice row has been persisted.
 *
 * Scope:
 *   - Direct user-pays-platform card payments (no Stripe Connect split).
 *   - Used for kind='wallet_topup', and any other user→platform invoice
 *     that should be paid by card (subscriptions, ads, premium features,
 *     platform-fee top-ups, etc.).
 *
 * NOT for marketplace flows where money is split with a seller — that's
 * StripeConnectRail (Step 8 of the Track 4 plan).
 */
class StripeRail implements PaymentRailInterface
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly string $defaultCurrency,
        private readonly string $successUrl,
        private readonly string $cancelUrl,
    ) {
    }

    public function name(): PaymentRail
    {
        return PaymentRail::Stripe;
    }

    /**
     * Stripe (non-Connect) handles user→platform invoices. Marketplace
     * flow needs StripeConnect. Anything else falls through to other rails.
     */
    public function supports(InvoiceContext $ctx): bool
    {
        return ! $ctx->isMarketplace();
    }

    public function initiate(Invoice $invoice): Invoice
    {
        $currency = strtolower($invoice->currency);
        // Stripe takes amounts in MINOR units (cents for USD).
        $amountMinor = (int) round((float) $invoice->amount * 100);

        try {
            $session = $this->stripe->checkout->sessions->create([
                'mode'                 => 'payment',
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => [
                            // Human-readable line item shown on the Stripe
                            // hosted checkout page. Keep generic so the
                            // same template works for any kind of invoice.
                            'name' => sprintf('%s #%d', $invoice->kind, $invoice->id),
                        ],
                        'unit_amount'  => $amountMinor,
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $this->successUrl,
                'cancel_url'  => $this->cancelUrl,
                // Echoed back on the webhook — useful for cross-checking
                // even though we look up by session id.
                'metadata'    => [
                    'invoice_id' => (string) $invoice->id,
                    'user_id'    => (string) $invoice->user_id,
                    'kind'       => $invoice->kind,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Stripe rejected the checkout session creation: ' . $e->getMessage(),
                previous: $e,
            );
        }

        // Persist the rail-attached fields on the invoice. Status stays
        // pending until the webhook confirms payment.
        $invoice->update([
            'partner_reference_id' => $session->id,
            'checkout_url'         => $session->url,
        ]);

        return $invoice->fresh();
    }

    /**
     * Issue a Stripe refund. Most refunds for our top-up flow come INBOUND
     * via the dashboard (admin clicks refund → Stripe webhook fires), but
     * this method exists for the case where we want to initiate refunds
     * programmatically — e.g. an admin tool, or a service-failed-to-deliver
     * automation in the marketplace flow once StripeConnect is in.
     *
     * The InvoiceRefund row is created here; applying it to the buyer's
     * platform credit (via WalletService::refund) and flipping the parent
     * invoice's status to Refunded for full refunds happen at the caller.
     */
    public function refund(Invoice $invoice, string $amount, string $reason): InvoiceRefund
    {
        $paymentIntentId = $invoice->metadata['stripe_payment_intent_id'] ?? null;

        if ($paymentIntentId === null) {
            throw new PaymentInitiationException(sprintf(
                'Cannot refund invoice %d: no stripe_payment_intent_id in metadata. '
                . 'This invoice may not have completed via Stripe Checkout.',
                $invoice->id,
            ));
        }

        $amountMinor = (int) round((float) $amount * 100);

        try {
            $stripeRefund = $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount'         => $amountMinor,
                'reason'         => $this->mapReasonToStripe($reason),
                'metadata'       => [
                    'invoice_id' => (string) $invoice->id,
                    'reason'     => $reason,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Stripe rejected the refund creation: ' . $e->getMessage(),
                previous: $e,
            );
        }

        return InvoiceRefund::create([
            'invoice_id'        => $invoice->id,
            'wallet_id'         => $invoice->wallet_id,
            'amount'            => $amount,
            'currency'          => $invoice->currency,
            'partner_refund_id' => $stripeRefund->id,
            'reason'            => $reason,
            'metadata'          => [
                'stripe_charge_id'         => $stripeRefund->charge,
                'stripe_payment_intent_id' => $paymentIntentId,
            ],
        ]);
    }

    /**
     * Stripe accepts only a small enum for `reason`. Map our richer
     * RefundReason set to what Stripe will swallow; everything else is
     * recorded in metadata but reason is set to null on the API call
     * (Stripe falls back to "other").
     */
    private function mapReasonToStripe(string $reason): ?string
    {
        return match ($reason) {
            RefundReason::RequestedByCustomer->value => 'requested_by_customer',
            RefundReason::Duplicate->value           => 'duplicate',
            RefundReason::Fraudulent->value          => 'fraudulent',
            default                                  => null, // Chargeback, SellerFailed, PlatformComp, Other
        };
    }
}
