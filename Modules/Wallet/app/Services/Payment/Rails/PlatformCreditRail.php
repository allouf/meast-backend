<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Modules\Wallet\Contracts\PaymentRailInterface;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Exceptions\InsufficientFundsException;
use Modules\Wallet\Exceptions\PaymentInitiationException;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Models\InvoiceRefund;
use Modules\Wallet\Services\Payment\InvoiceContext;
use Modules\Wallet\Services\WalletService;

/**
 * Instant-debit rail backed by the user's Platform Credit balance.
 *
 * NOT an external provider. The "rail" here is just our own `wallets`
 * table — we debit the buyer's available balance, mark the invoice
 * Completed inline, and return. There's no checkout URL, no webhook,
 * no asynchronous completion.
 *
 * When this rail wins resolution:
 *   - Caller passed `preferredRail='platform_credit'` AND
 *   - It's not a marketplace flow (sellers can't be paid in platform
 *     credit — credit doesn't transfer between users) AND
 *   - The buyer has sufficient balance
 *
 * If a buyer chose "use my credit" but their balance is short, this
 * rail's initiate() throws InsufficientFundsException and the
 * surrounding PaymentInitiator transaction rolls back. The frontend
 * should re-prompt the user with a different payment method.
 */
class PlatformCreditRail implements PaymentRailInterface
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {
    }

    public function name(): PaymentRail
    {
        return PaymentRail::PlatformCredit;
    }

    /**
     * Only valid when the caller explicitly requested platform credit
     * (it's never a default fallback) AND the flow isn't marketplace.
     *
     * Note: we deliberately DON'T check balance in supports() — that's
     * an initiate-time concern. supports() answers "can I handle this
     * shape of context?", not "are present conditions sufficient?".
     */
    public function supports(InvoiceContext $ctx): bool
    {
        // Marketplace flow needs to deliver funds to a seller.
        // Platform credit can't do that — it's an internal ledger.
        if ($ctx->isMarketplace()) {
            return false;
        }

        // Explicit-choice only. We never silently default to debiting
        // the user's credit — they have to opt in via preferredRail.
        return $ctx->preferredRail === PaymentRail::PlatformCredit->value;
    }

    public function initiate(Invoice $invoice): Invoice
    {
        // Debit the buyer's available balance. Throws InsufficientFundsException
        // (which the surrounding PaymentInitiator transaction catches; the
        // invoice we created moments ago gets rolled back, and the caller
        // sees a 422 with the underlying error message).
        try {
            $tx = $this->wallets->debit(
                userId:    $invoice->user_id,
                amount:    (string) $invoice->amount,
                reference: ['type' => Invoice::class, 'id' => $invoice->id],
                metadata:  [
                    'gateway'      => 'platform_credit',
                    'invoice_kind' => $invoice->kind,
                ],
            );
        } catch (InsufficientFundsException $e) {
            throw new PaymentInitiationException(
                'Platform credit balance is insufficient for this purchase.',
                previous: $e,
            );
        }

        // Mark Completed inline. No checkout URL, no webhook required.
        // The synthetic partner_reference_id 'platform_credit:<tx_id>'
        // gives every rail a unique handle even when there's no external
        // provider id to record (kept consistent with the (rail,
        // partner_reference_id) unique constraint).
        $invoice->markCompleted(
            partnerReferenceId: 'platform_credit:' . $tx->id,
            metadataPatch:      ['wallet_transaction_id' => $tx->id],
            actorType:          'system',
        );

        return $invoice->fresh();
    }

    /**
     * Refund a credit-paid invoice by re-crediting the buyer's wallet.
     *
     * Mirrors the debit done at initiate(): use WalletService::refund
     * (REFUND transaction type) to put the funds back in available_balance,
     * then create the InvoiceRefund row to record what happened.
     *
     * Caller is responsible for transitioning the invoice's status
     * (markRefunded for full refunds) — this method just handles the
     * money movement + audit row.
     */
    public function refund(Invoice $invoice, string $amount, string $reason): InvoiceRefund
    {
        $tx = $this->wallets->refund(
            userId:    $invoice->user_id,
            amount:    $amount,
            reference: ['type' => InvoiceRefund::class, 'id' => 0], // patched below
            metadata:  [
                'gateway'    => 'platform_credit',
                'invoice_id' => $invoice->id,
                'reason'     => $reason,
            ],
        );

        $refund = InvoiceRefund::create([
            'invoice_id'            => $invoice->id,
            'wallet_id'             => $invoice->wallet_id,
            'amount'                => $amount,
            'currency'              => $invoice->currency,
            'partner_refund_id'     => 'platform_credit:' . $tx->id,
            'reason'                => $reason,
            'metadata'              => ['wallet_transaction_id' => $tx->id],
            'wallet_transaction_id' => $tx->id,
        ]);

        // Back-fill the credit-ledger row's reference id now that we know it.
        $tx->update(['reference_id' => $refund->id]);

        return $refund;
    }
}
