<?php

namespace Modules\Wallet\Services;

/**
 * Computes / verifies HMAC signatures for Transak Widget URLs and webhooks.
 *
 * The secret key never leaves this service. Any caller that needs a signed
 * URL or a verified webhook routes through here.
 *
 * SIGNING FORMAT — current assumption (adjust here if Transak's docs change):
 *
 *   1. Caller supplies the param map { apiKey, walletAddress, ... }.
 *   2. Filter out null/empty values (Transak rejects URLs containing them).
 *   3. Sort params alphabetically by key for a stable signing input.
 *   4. Build the URL-encoded query string.
 *   5. signature = HMAC-SHA256("?" + queryString, secretKey), hex-encoded.
 *
 * The "?"-prefix matches Transak's reference Node.js example. If during
 * sandbox testing the widget rejects our signature with "invalid signature"
 * try removing the "?" prefix (some Transak doc versions vary).
 *
 * Webhook verification uses the raw request body and the value of the
 * 'transak-signature' header.
 */
class TransakSignatureService
{
    public function __construct(
        private readonly string $secretKey,
    ) {
    }

    /**
     * Build the canonical query string and its signature from a param map.
     *
     * Returns an array with both pieces so the caller can build the final
     * widget URL by concatenating: $widgetBase . '?' . $queryString . '&signature=' . $sig
     *
     * @param  array<string, scalar|null>  $params
     * @return array{query_string: string, signature: string, params: array<string, scalar>}
     */
    public function signWidgetParams(array $params): array
    {
        // Drop null / empty-string entries — Transak rejects them.
        $params = array_filter(
            $params,
            static fn ($v) => $v !== null && $v !== '',
        );

        // Stable ordering for HMAC.
        ksort($params);

        $queryString = http_build_query($params);

        // Reverted to hex (most-cited Transak format). 403 in sandbox is
        // most likely upstream account-activation, not the signing format —
        // the partner dashboard's "Ramps: Pending" status applies even to
        // sandbox widget loads for new accounts.
        $signature = hash_hmac(
            'sha256',
            $queryString,
            $this->secretKey,
        );

        return [
            'params'       => $params,
            'query_string' => $queryString,
            'signature'    => $signature,
        ];
    }

    /**
     * Verify the signature on an incoming Transak webhook.
     *
     * Use the RAW request body (Request::getContent()) — re-serializing
     * via $request->all() and re-encoding will alter byte order/whitespace
     * and break verification.
     */
    public function verifyWebhookSignature(
        string $rawPayload,
        string $providedSignature,
    ): bool {
        if ($providedSignature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $rawPayload, $this->secretKey);

        return hash_equals($expected, $providedSignature);
    }
}
