<?php

namespace Modules\Wallet\Contracts;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Models\Invoice;

/**
 * The single contract every other module uses when money needs to flow.
 *
 * See `docs/payment-integration-contract.md` for the public-facing spec
 * (the doc that's distributed to feature-module developers). This file is
 * the formal Laravel/PHP type that implements that contract.
 *
 * Resolution order inside initiate():
 *   1. If $idempotencyKey matches an existing invoice → return that invoice
 *      (true idempotency — re-running with the same key gives the same row).
 *   2. If $referenceable already has a pending invoice → return that one
 *      (concurrent-payment prevention — duplicate clicks don't double-bill).
 *   3. Otherwise create a new invoice and call the resolved rail's
 *      initiate() to attach the rail-side resource (checkout URL, etc.).
 *
 * The returned Invoice always has status set (typically 'pending', or
 * 'completed' for instant rails like PlatformCredit) and — for non-instant
 * rails — a checkout_url the frontend can redirect the user to.
 */
interface PaymentInitiatorInterface
{
    /**
     * Start (or resume) a payment for any buyable thing.
     *
     * @param  string       $kind           Snake_case payment-type identifier.
     * @param  int          $buyerUserId    Who pays.
     * @param  int|null     $sellerUserId   Who receives (NULL = user→platform).
     * @param  string       $amount         Decimal string, major units (e.g. "99.00").
     * @param  string       $currency       ISO 4217 (currently 'USD' / 'usd').
     * @param  Model|null   $referenceable  Caller's domain model (ServiceOrder etc.).
     *                                      May be NULL for internal flows like 'wallet_topup'
     *                                      where the Invoice itself is the source of truth.
     * @param  string|null  $idempotencyKey Caller-provided retry-safe key.
     * @param  array<string, mixed>  $metadata  Module-specific extras stored on the invoice.
     * @param  string|null  $preferredRail  Frontend hint ('stripe' / 'platform_credit' / etc.).
     *                                      Honoured if the rail can handle the context;
     *                                      otherwise the resolver picks a fallback.
     *
     * @return Invoice  Persisted, with rail attached and (for non-instant rails)
     *                  checkout_url populated.
     *
     * @throws \Modules\Wallet\Exceptions\PaymentInitiationException
     *         For validation failures, no rail can handle the request, or
     *         the rail provider rejects the request.
     */
    public function initiate(
        string $kind,
        int $buyerUserId,
        ?int $sellerUserId,
        string $amount,
        string $currency,
        ?Model $referenceable = null,
        ?string $idempotencyKey = null,
        array $metadata = [],
        ?string $preferredRail = null,
    ): Invoice;

    /**
     * Refund a Completed invoice through its original rail.
     *
     * Looks up the rail by the invoice's `rail` column and delegates to
     * its refund() method, which issues the provider-side reversal
     * (Stripe API call, wallet credit-back, crypto manual flag, etc.)
     * and creates the InvoiceRefund row.
     *
     * For a FULL refund (amount === invoice->amount), the parent invoice
     * is transitioned to REFUNDED via markRefunded — keeping the invoice's
     * own status accurate. For partial refunds the invoice stays COMPLETED
     * (each InvoiceRefund is the audit trail; the sum can be < invoice
     * amount across multiple partial refunds).
     *
     * Callers:
     *   - Domain modules cancelling their own paid orders (ServiceOrders,
     *     Boost Campaigns) — buyer-initiated full refunds
     *   - Admin tooling — partial / policy-driven refunds with operator
     *     judgment
     *
     * @param  Invoice                                                $invoice
     * @param  string                                                 $amount   Decimal string in major units.
     * @param  string                                                 $reason   One of \Modules\Wallet\Enums\RefundReason values.
     * @param  string                                                 $actorType  'user' / 'admin' / 'system' — recorded on the audit log.
     * @param  int|null                                               $actorId    DB id of the acting user/admin if applicable.
     * @return \Modules\Wallet\Models\InvoiceRefund
     *
     * @throws \Modules\Wallet\Exceptions\PaymentInitiationException
     *         If the rail rejects (e.g. Stripe API failure) or the invoice
     *         isn't in a refundable state.
     * @throws \Modules\Wallet\Exceptions\RefundNotSupportedException
     *         If the rail doesn't support programmatic refunds (e.g.
     *         NowPayments — crypto manual flow). Caller routes to admin
     *         tooling instead.
     */
    public function refund(
        Invoice $invoice,
        string $amount,
        string $reason,
        string $actorType = 'system',
        ?int $actorId = null,
    ): \Modules\Wallet\Models\InvoiceRefund;
}
