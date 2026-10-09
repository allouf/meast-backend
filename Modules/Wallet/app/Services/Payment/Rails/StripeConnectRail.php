<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Illuminate\Support\Facades\Log;
use Modules\Wallet\Contracts\PaymentRailInterface;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Enums\RefundReason;
use Modules\Wallet\Exceptions\PaymentInitiationException;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Models\SellerStripeAccount;
use Modules\Wallet\Services\Payment\InvoiceContext;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Marketplace card-payments rail using Stripe Connect (destination charges).
 *
 * Different from StripeRail in three ways:
 *   1. supports() ONLY when seller_user_id is set (marketplace flow).
 *   2. Creates a Stripe Checkout Session with `payment_intent_data.transfer_data`
 *      pointing at the seller's connected account — Stripe routes the funds
 *      directly to the seller minus an `application_fee_amount` that lands
 *      in our platform account.
 *   3. Refunds by default reverse the application fee (configurable via
 *      services.stripe_connect.refund_application_fee).
 *
 * Application fee resolution: per-`kind` decimal in services.stripe_connect.
 * application_fees, falling back to the 'default' entry. Multiplied by the
 * invoice's gross amount, rounded to the nearest cent (Stripe takes minor units).
 *
 * Seller-onboarding gate: supports() returns false for sellers whose
 * SellerStripeAccount row has charges_enabled=false. Frontend should
 * redirect those sellers to /api/v1/seller/stripe-onboarding before
 * letting them list services.
 */
class StripeConnectRail implements PaymentRailInterface
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly string $successUrl,
        private readonly string $cancelUrl,
        /** @var array<string, float>  Per-kind fee % (e.g. ['service_order' => 0.07]). */
        private readonly array $applicationFees,
        private readonly bool $refundApplicationFee,
    ) {
    }

    public function name(): PaymentRail
    {
        return PaymentRail::StripeConnect;
    }

    /**
     * Marketplace flow only AND seller's Connect account is ready for
     * charges. We DON'T return false to silently fall back to another
     * rail — there's no other rail that handles marketplace flow today,
     * so a "not ready" seller results in NoSupportedRailException at
     * the resolver, which the controller surfaces as 422 with a clear
     * message about completing onboarding.
     */
    public function supports(InvoiceContext $ctx): bool
    {
        if (! $ctx->isMarketplace()) {
            return false;
        }

        $seller = SellerStripeAccount::query()
            ->where('user_id', $ctx->sellerUserId)
            ->first();

        if ($seller === null) {
            // Seller never onboarded with Stripe Connect — frontend should
            // route them through onboarding before they can transact.
            return false;
        }

        return $seller->isReadyForCharges();
    }

    public function initiate(Invoice $invoice): Invoice
    {
        // Re-fetch the seller's Connect account inside initiate() (defence
        // against the rare race where supports() said yes and onboarding
        // state flipped to false before we got here).
        $seller = SellerStripeAccount::query()
            ->where('user_id', $invoice->seller_user_id)
            ->first();

        if ($seller === null || ! $seller->isReadyForCharges()) {
            throw new PaymentInitiationException(sprintf(
                'Seller (user %d) is not yet ready to accept charges via Stripe Connect.',
                $invoice->seller_user_id,
            ));
        }

        $currency      = strtolower($invoice->currency);
        $grossMinor    = (int) round((float) $invoice->amount * 100);
        $feeMinor      = $this->computeApplicationFeeMinor($invoice->kind, $grossMinor);
        $sellerPayout  = $grossMinor - $feeMinor;

        try {
            // Destination-charges flavour of Stripe Checkout. Stripe pulls
            // the buyer's card, takes our application fee off the top, and
            // transfers the remainder to the seller's connected account —
            // all in one operation, all auto-reconciled by Stripe.
            $session = $this->stripe->checkout->sessions->create([
                'mode'                 => 'payment',
                'payment_method_types' => ['card'],
                'line_items'           => [[
                    'price_data' => [
                        'currency'     => $currency,
                        'product_data' => [
                            'name' => sprintf('%s #%d', $invoice->kind, $invoice->id),
                        ],
                        'unit_amount'  => $grossMinor,
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => $this->successUrl,
                'cancel_url'  => $this->cancelUrl,
                'payment_intent_data' => [
                    'application_fee_amount' => $feeMinor,
                    'transfer_data' => [
                        'destination' => $seller->stripe_account_id,
                    ],
                ],
                'metadata' => [
                    'invoice_id'        => (string) $invoice->id,
                    'buyer_user_id'     => (string) $invoice->user_id,
                    'seller_user_id'    => (string) $invoice->seller_user_id,
                    'kind'              => $invoice->kind,
                    'platform_fee_minor'=> (string) $feeMinor,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Stripe rejected the Connect checkout session: ' . $e->getMessage(),
                previous: $e,
            );
        }

        // Persist the rail-attached fields and the computed split. The
        // fee/payout amounts are stored in major units (decimal:8), matching
        // the rest of the schema.
        $invoice->update([
            'partner_reference_id' => $session->id,
            'checkout_url'         => $session->url,
            'platform_fee_amount'  => bcdiv((string) $feeMinor,    '100', 8),
            'seller_payout_amount' => bcdiv((string) $sellerPayout,'100', 8),
        ]);

        return $invoice->fresh();
    }

    /**
     * Issue a refund for a Connect charge.
     *
     * If `refund_application_fee` is true (default), Stripe reverses BOTH
     * the seller's portion AND our app fee. False would let us keep the
     * fee even on refunded transactions — possible but seller-unfriendly.
     */
    public function refund(Invoice $invoice, string $amount, string $reason): InvoiceRefund
    {
        $paymentIntentId = $invoice->metadata['stripe_payment_intent_id'] ?? null;

        if ($paymentIntentId === null) {
            throw new PaymentInitiationException(sprintf(
                'Cannot refund Connect invoice %d: no stripe_payment_intent_id in metadata.',
                $invoice->id,
            ));
        }

        $amountMinor = (int) round((float) $amount * 100);

        try {
            $stripeRefund = $this->stripe->refunds->create([
                'payment_intent'         => $paymentIntentId,
                'amount'                 => $amountMinor,
                'reason'                 => $this->mapReasonToStripe($reason),
                'refund_application_fee' => $this->refundApplicationFee,
                // For Connect destination charges, this also reverses the
                // transfer to the seller. Always true here — keeping a
                // transferred refund without reversing the transfer leaves
                // accounting in a hard-to-explain state.
                'reverse_transfer'       => true,
                'metadata'               => [
                    'invoice_id' => (string) $invoice->id,
                    'reason'     => $reason,
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new PaymentInitiationException(
                'Stripe rejected the Connect refund: ' . $e->getMessage(),
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
                'application_fee_refunded' => $this->refundApplicationFee,
            ],
        ]);
    }

    /**
     * Per-kind percentage × gross, rounded to nearest minor unit.
     * Falls back to the 'default' entry if the kind isn't explicitly
     * configured. Logs a warning on fallback so finance / engineering
     * can spot misconfigurations from the logs.
     */
    private function computeApplicationFeeMinor(string $kind, int $grossMinor): int
    {
        $rate = $this->applicationFees[$kind] ?? null;

        if ($rate === null) {
            $rate = $this->applicationFees['default'] ?? 0.07;
            Log::info('StripeConnectRail using default application fee', [
                'kind' => $kind,
                'rate' => $rate,
            ]);
        }

        // Half-up rounding gives the platform the benefit of fractional
        // cents on average; for a 7%-of-$99 invoice that's a $6.93 fee,
        // exactly. Larger fractions get rounded normally.
        return (int) round($grossMinor * $rate);
    }

    /** Same Stripe-reason mapping as StripeRail. */
    private function mapReasonToStripe(string $reason): ?string
    {
        return match ($reason) {
            RefundReason::RequestedByCustomer->value => 'requested_by_customer',
            RefundReason::Duplicate->value           => 'duplicate',
            RefundReason::Fraudulent->value          => 'fraudulent',
            default                                  => null,
        };
    }
}
