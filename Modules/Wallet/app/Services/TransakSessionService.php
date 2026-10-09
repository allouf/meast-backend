<?php

namespace Modules\Wallet\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Creates a single-use Transak Widget URL via the session API.
 *
 *   POST /api/v2/auth/session
 *   Headers: { 'access-token': <Partner Access Token> }
 *   Body:    { 'widgetParams': { apiKey, referrerDomain, ... } }
 *   Returns: { data: { widgetUrl } }
 *
 * Replaces the old "build URL + HMAC sign locally" flow. Transak now
 * stores the widget params server-side keyed by a sessionId, and returns
 * a one-shot widgetUrl that:
 *   - Expires 5 minutes after creation
 *   - Cannot be reopened after first use
 *   - Carries no params in clear text (just `?apiKey=…&sessionId=…`)
 *
 * Frontend implication: the widgetUrl must be requested immediately
 * before opening the widget. Don't cache it.
 *
 * Backend implication: the partner backend's outbound IP must be
 * whitelisted in the Transak partner dashboard. Direct frontend calls
 * to /api/v2/auth/session are rejected.
 */
class TransakSessionService
{
    /** Endpoint path on the Transak API host. */
    private const SESSION_PATH = '/api/v2/auth/session';

    public function __construct(
        private readonly string             $apiBase,
        private readonly string             $apiKey,
        private readonly string             $referrerDomain,
        private readonly TransakAuthService $auth,
    ) {
    }

    /**
     * Create a new widget session.
     *
     * @param  array<string, scalar|null>  $widgetParams  Caller-supplied widget
     *   params (walletAddress, cryptoCurrencyCode, network, etc.). The service
     *   automatically injects apiKey + referrerDomain — callers don't need to.
     *
     * @return array{widget_url: string, raw: array<string, mixed>}
     *
     * @throws \RuntimeException If the access token is missing or Transak
     *   returns an error.
     */
    public function createSession(array $widgetParams): array
    {
        // Force-inject the two mandatory fields. Caller-provided values
        // would be overwritten by these — we don't trust callers to set
        // the partner identity correctly.
        $widgetParams['apiKey']         = $this->apiKey;
        $widgetParams['referrerDomain'] = $this->referrerDomain;

        // Drop empty strings / nulls — Transak rejects empty values for
        // optional fields (e.g. an empty email triggers a 400).
        $widgetParams = array_filter(
            $widgetParams,
            static fn ($v) => $v !== null && $v !== '',
        );

        $response = $this->postSession($widgetParams);

        // If the cached access token has been revoked or otherwise rejected,
        // the session API returns 401. Drop the cached token and retry once
        // with a fresh one.
        if ($response->status() === 401) {
            $this->auth->forgetCachedToken();
            $response = $this->postSession($widgetParams);
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Transak session API returned %d: %s',
                $response->status(),
                $response->body(),
            ));
        }

        $widgetUrl = $response->json('data.widgetUrl');

        if (! is_string($widgetUrl) || $widgetUrl === '') {
            throw new RuntimeException(
                'Transak session API succeeded but returned no widgetUrl. '
                . 'Body: ' . $response->body(),
            );
        }

        return [
            'widget_url' => $widgetUrl,
            'raw'        => (array) $response->json('data', []),
        ];
    }

    /**
     * Send the session POST. Pulled out so the 401-retry path can reuse it
     * without duplicating the request setup.
     *
     * @param  array<string, scalar>  $widgetParams
     */
    private function postSession(array $widgetParams): \Illuminate\Http\Client\Response
    {
        return $this->client()->post(self::SESSION_PATH, [
            'widgetParams' => $widgetParams,
        ]);
    }

    /**
     * Configured Http client with the (possibly just-refreshed) access-token
     * header set. Token resolution happens here so retries always pick up
     * the latest one.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl($this->apiBase)
            ->withHeaders([
                'access-token' => $this->auth->getAccessToken(),
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->timeout(15);
    }
}
