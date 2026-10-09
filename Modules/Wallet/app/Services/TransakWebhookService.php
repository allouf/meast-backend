<?php

namespace Modules\Wallet\Services;

use Illuminate\Support\Facades\Log;
use Modules\Wallet\Models\TransakOrder;

/**
 * Processes verified Transak webhook events.
 *
 * Idempotency is anchored on transak_order_id (unique column on
 * transak_orders). updateOrCreate() re-runs cleanly if Transak retries,
 * because we always set the same id and overwrite the latest state.
 *
 * IMPORTANT: this service does NOT credit the user's wallet, swap funds,
 * or move money. The crypto purchase is settled directly between Transak
 * and the user's external wallet. We're just keeping a local record of
 * what happened for analytics, order history, and audit.
 *
 * If at some future point we want to grant platform credit/discount on
 * a successful Transak buy, that hook lives here — but it's NOT in
 * scope for sandbox bring-up.
 */
class TransakWebhookService
{
    public function __construct(
        private readonly TransakSignatureService $signer,
    ) {
    }

    /**
     * Verify signature + persist the order state.
     *
     * @throws \RuntimeException  If signature is invalid.
     */
    public function handle(string $rawPayload, string $signatureHeader): void
    {
        if (! $this->signer->verifyWebhookSignature($rawPayload, $signatureHeader)) {
            throw new \RuntimeException('Invalid Transak webhook signature');
        }

        $event = json_decode($rawPayload, true);
        if (! is_array($event)) {
            Log::warning('Transak webhook payload was not valid JSON', [
                'raw' => $rawPayload,
            ]);
            return;
        }

        // Transak typically sends { eventID, webhookData: { id, status, ... } }
        // but the exact envelope may vary by event type. We defensively try
        // both shapes.
        $data = $event['webhookData']
            ?? $event['data']
            ?? $event;

        $transakOrderId = $data['id'] ?? null;

        if (! $transakOrderId) {
            Log::warning('Transak webhook missing order id', ['event' => $event]);
            return;
        }

        // partnerCustomerId we passed during sign-url == our user id.
        $userId = isset($data['partnerCustomerId'])
            ? (int) $data['partnerCustomerId']
            : null;

        TransakOrder::query()->updateOrCreate(
            ['transak_order_id' => $transakOrderId],
            [
                'partner_order_id' => $data['partnerOrderId']      ?? null,
                'user_id'          => $userId,
                'status'           => $data['status']               ?? 'UNKNOWN',
                'fiat_currency'    => $data['fiatCurrency']         ?? null,
                'fiat_amount'      => $data['fiatAmount']           ?? null,
                'crypto_currency'  => $data['cryptocurrency']
                                      ?? $data['cryptoCurrency']    ?? null,
                'crypto_amount'    => $data['cryptoAmount']         ?? null,
                'network'          => $data['network']              ?? null,
                'wallet_address'   => $data['walletAddress']        ?? null,
                'payload'          => $event,
            ],
        );
    }
}
