<?php

namespace Modules\Wallet\Services\Payment\Rails;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Models\Invoice;
use Modules\Wallet\Services\WalletService;

/**
 * Processes incoming NowPayments IPN (Instant Payment Notification) events.
 *
 * The webhook controller calls verifyAndParse() to authenticate and decode
 * the raw payload, then handle() to dispatch on payment_status. Dedup
 * against retries (via the webhook_events table) is the controller's
 * responsibility — this handler assumes a verified, non-duplicate payload.
 *
 * Status dispatch (per strict policy — Option A):
 *
 *   finished        → walletService->credit + invoice->markCompleted
 *   partially_paid  → invoice->markFailed (under-payment, no wallet credit)
 *   failed          → invoice->markFailed
 *   expired         → invoice->markExpired
 *   waiting/confirming/confirmed/sending/refunded → log only (no state change)
 *
 * Re-deposit handling: if parent_payment_id is set, the IPN is a subsequent
 * send to an address whose original invoice was already paid. Strict policy:
 * ignore — manual reconciliation only. Auto-crediting these risks silent
 * double-credit on user error and contradicts NowPayments' own guidance.
 *
 * Signature verification (per NowPayments docs):
 *   1. Decode the raw JSON body
 *   2. Recursively sort keys (tksort)
 *   3. JSON-encode with JSON_UNESCAPED_SLASHES → canonical string
 *   4. HMAC-SHA512 with the IPN secret
 *   5. Compare to x-nowpayments-sig header (hash_equals, timing-safe)
 *
 * The "All-Strings" webhook format (set in the NP dashboard) ensures every
 * field arrives as a string, so the decode→ksort→encode round-trip is
 * deterministic and avoids int/float serialization edge cases that bite
 * the "Classic" format.
 */
class NowPaymentsWebhookHandler
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly string $ipnSecret,
    ) {
    }

    /**
     * Verify the x-nowpayments-sig header against the raw body. Returns the
     * parsed payload on success, null on any verification failure. No
     * exceptions — the caller maps null → 400 itself.
     */
    public function verifyAndParse(string $rawBody, string $signature): ?array
    {
        if ($signature === '' || $rawBody === '') {
            return null;
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            return null;
        }

        $sorted = $payload;
        $this->ksortRecursive($sorted);
        $canonical = json_encode($sorted, JSON_UNESCAPED_SLASHES);
        if ($canonical === false) {
            return null;
        }

        $expected = hash_hmac('sha512', $canonical, $this->ipnSecret);

        return hash_equals($expected, $signature) ? $payload : null;
    }

    /**
     * Dispatch a verified IPN payload to the appropriate handler based on
     * payment_status. Idempotent — repeat deliveries are safe to call.
     */
    public function handle(array $payload): void
    {
        $paymentStatus = $payload['payment_status'] ?? null;

        if ($paymentStatus === null) {
            Log::warning('NowPayments IPN missing payment_status', ['payload' => $payload]);
            return;
        }

        // Re-deposits (additional sends to a previously-used address) are
        // ignored. We don't auto-credit them — see class docblock.
        if (! empty($payload['parent_payment_id'])) {
            Log::info('NowPayments IPN is a re-deposit — ignoring per strict policy', [
                'payment_id'        => $payload['payment_id'] ?? null,
                'parent_payment_id' => $payload['parent_payment_id'],
                'status'            => $paymentStatus,
            ]);
            return;
        }

        $invoice = $this->findInvoiceFromPayload($payload);
        if ($invoice === null) {
            return; // already logged in findInvoiceFromPayload
        }

        match ($paymentStatus) {
            'finished'       => $this->onFinished($invoice, $payload),
            'partially_paid' => $this->onPartiallyPaid($invoice, $payload),
            'failed'         => $this->onFailed($invoice, $payload),
            'expired'        => $this->onExpired($invoice, $payload),
            // waiting, confirming, confirmed, sending, refunded → no state change
            default          => Log::info('NowPayments IPN — informational status, no action', [
                'payment_id' => $payload['payment_id'] ?? null,
                'invoice_id' => $invoice->id,
                'status'     => $paymentStatus,
            ]),
        };
    }

    /* ─────────────────────────── status handlers ─────────────────────────── */

    private function onFinished(Invoice $invoice, array $payload): void
    {
        if ($invoice->isCompleted()) {
            return; // already processed by an earlier IPN
        }

        // Credit the buyer's wallet first (so a wallet failure rolls back
        // before the invoice transitions). The amount credited is the
        // invoice's price — fees were paid by the user (is_fee_paid_by_user=true),
        // so the user gets full value, regardless of NP's outcome_amount.
        DB::transaction(function () use ($invoice, $payload) {
            $this->walletService->credit(
                userId:    $invoice->user_id,
                amount:    (string) $invoice->amount,
                reference: ['type' => Invoice::class, 'id' => $invoice->id],
                metadata:  $this->buildRailMetadata($payload),
            );

            $invoice->markCompleted(
                metadataPatch: $this->buildRailMetadata($payload),
                actorType:     'webhook',
            );
        });
    }

    private function onPartiallyPaid(Invoice $invoice, array $payload): void
    {
        if (! $invoice->isPending()) {
            return;
        }

        $invoice->markFailed(
            sprintf(
                'Under-payment via NowPayments: expected %s %s, received %s %s.',
                $payload['pay_amount']     ?? 'unknown',
                $payload['pay_currency']   ?? 'unknown',
                $payload['actually_paid']  ?? 'unknown',
                $payload['pay_currency']   ?? 'unknown',
            ),
            actorType: 'webhook',
        );
    }

    private function onFailed(Invoice $invoice, array $payload): void
    {
        if (! $invoice->isPending()) {
            return;
        }

        $invoice->markFailed(
            'NowPayments reported failure for payment ' . ($payload['payment_id'] ?? '(unknown)'),
            actorType: 'webhook',
        );
    }

    private function onExpired(Invoice $invoice, array $payload): void
    {
        if (! $invoice->isPending()) {
            return;
        }

        $invoice->markExpired(actorType: 'webhook');
    }

    /* ─────────────────────────── helpers ─────────────────────────── */

    /**
     * Locate the local Invoice for an IPN payload. Prefers NP's invoice_id
     * (matches our partner_reference_id directly), falls back to parsing
     * our INV-{id} prefix out of order_id. Returns null + logs if neither
     * leads to a known invoice.
     */
    private function findInvoiceFromPayload(array $payload): ?Invoice
    {
        $partnerRef = $payload['invoice_id'] ?? null;
        if ($partnerRef !== null && $partnerRef !== '') {
            $invoice = Invoice::query()
                ->where('rail', PaymentRail::NowPayments->value)
                ->where('partner_reference_id', (string) $partnerRef)
                ->first();

            if ($invoice !== null) {
                return $invoice;
            }
        }

        $orderId = $payload['order_id'] ?? null;
        if (is_string($orderId) && preg_match('/^INV-(\d+)$/', $orderId, $m)) {
            $invoice = Invoice::query()
                ->where('id', (int) $m[1])
                ->where('rail', PaymentRail::NowPayments->value)
                ->first();

            if ($invoice !== null) {
                return $invoice;
            }
        }

        Log::warning('NowPayments IPN references unknown invoice', [
            'payment_id' => $payload['payment_id'] ?? null,
            'invoice_id' => $payload['invoice_id'] ?? null,
            'order_id'   => $payload['order_id'] ?? null,
        ]);

        return null;
    }

    /**
     * Extract the NP-specific fields we want to persist on both the wallet
     * transaction's metadata AND the invoice's metadataPatch. Keeps the two
     * call sites in sync.
     */
    private function buildRailMetadata(array $payload): array
    {
        return [
            'gateway'                => 'nowpayments',
            'nowpayments_payment_id' => (string) ($payload['payment_id'] ?? ''),
            'pay_currency'           => $payload['pay_currency']   ?? null,
            'pay_address'            => $payload['pay_address']    ?? null,
            'actually_paid'          => $payload['actually_paid']  ?? null,
            'outcome_amount'         => $payload['outcome_amount'] ?? null,
            'outcome_currency'       => $payload['outcome_currency'] ?? null,
        ];
    }

    /**
     * Recursive ksort, matching NowPayments' tksort reference in their
     * PHP docs example. Mutates by reference, sorts at every depth.
     */
    private function ksortRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }
}
