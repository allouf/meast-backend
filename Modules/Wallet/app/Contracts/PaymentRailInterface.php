<?php

namespace Modules\Wallet\Contracts;

use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Services\Payment\InvoiceContext;

/**
 * Strategy interface for a payment provider.
 *
 * Each rail (Stripe, StripeConnect, PlatformCredit, NowPayments, ...)
 * implements this. RailResolver picks the right one based on the
 * InvoiceContext at initiate-time.
 *
 * Lifecycle a rail handles for an Invoice:
 *   1. supports(ctx)        — gate check during resolution
 *   2. initiate(invoice)    — rail-side resource creation (checkout session,
 *                              deposit address, instant debit, ...) — runs
 *                              AFTER the invoice has been persisted, mutates
 *                              the invoice with rail-attached fields and
 *                              returns the (refreshed) invoice.
 *   3. (rail-side webhook arrives — handled by the rail's webhook controller,
 *      not by this interface; the controller calls invoice->markCompleted /
 *      markFailed / markRefunded as appropriate.)
 *   4. refund(invoice,...)  — for proactive refunds we initiate ourselves.
 *      For Stripe etc. where refunds typically come INBOUND via dashboard,
 *      a rail may make refund() a thin wrapper around the provider's API.
 */
interface PaymentRailInterface
{
    /** Identifier used in invoices.rail column. */
    public function name(): PaymentRail;

    /**
     * Can this rail handle this combination of inputs?
     *
     * Called by RailResolver during initiate() to find a viable rail.
     * Return false if any required prerequisite is missing (e.g.
     * StripeConnectRail returns false if the seller's connected account
     * isn't yet onboarded).
     */
    public function supports(InvoiceContext $ctx): bool;

    /**
     * Create the rail-side resource for an already-persisted Invoice
     * (status=pending). Mutates the invoice with checkout_url +
     * partner_reference_id (or marks it Completed inline for instant rails)
     * and returns the refreshed model.
     *
     * @throws \Modules\Wallet\Exceptions\PaymentInitiationException
     *         If the provider rejects the request.
     */
    public function initiate(Invoice $invoice): Invoice;

    /**
     * Issue a refund (full or partial) against a Completed invoice.
     * Returns the InvoiceRefund row created. Caller is responsible for
     * applying the refund to the buyer's platform credit (if relevant)
     * and for transitioning the invoice's status to Refunded for full
     * refunds — the rail just handles the provider call + record.
     *
     * @param  string  $amount   Decimal string in major units.
     * @param  string  $reason   One of \Modules\Wallet\Enums\RefundReason values.
     */
    public function refund(Invoice $invoice, string $amount, string $reason): InvoiceRefund;
}
