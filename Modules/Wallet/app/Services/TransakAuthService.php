<?php

namespace Modules\Wallet\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Manages Transak's Partner Access Token lifecycle.
 *
 *   POST {auth_api}/partners/api/v2/refresh-token
 *   Headers: { 'api-secret': <secret_key> }
 *   Body:    { 'apiKey': <public_key> }
 *   Returns: { data: { accessToken, expiresAt } }
 *
 * The token has a 7-day validity. Transak's docs explicitly warn against
 * refreshing on every request — they rate-limit. So we cache it.
 *
 * Caching strategy:
 *   - On miss: hit the refresh endpoint, store accessToken in cache with
 *     a TTL of 6 days (one-day safety margin under the documented 7-day expiry).
 *   - On hit: return the cached token.
 *   - On 401 from session API (token rejected): the caller should call
 *     forgetCachedToken() and retry once.
 *
 * Manual override: if config('services.transak.access_token') is set
 * (env: TRANSAK_ACCESS_TOKEN), that value wins. Useful for debugging
 * or first-time bootstrapping when you've manually generated a token.
 */
class TransakAuthService
{
    /** Cache key for the access token. Versioned in case format ever changes. */
    private const CACHE_KEY = 'transak:access_token:v1';

    /** Refresh endpoint path on the auth API host. */
    private const REFRESH_PATH = '/partners/api/v2/refresh-token';

    /**
     * Cache TTL in seconds. One day shy of the documented 7-day expiry,
     * so we proactively refresh before tokens go stale.
     */
    private const CACHE_TTL_SECONDS = 6 * 24 * 60 * 60;

    public function __construct(
        private readonly string  $authApiBase,
        private readonly string  $apiKey,
        private readonly string  $apiSecret,
        private readonly ?string $manualOverride = null,
    ) {
    }

    /**
     * Return a usable access token. Generates and caches a new one if
     * the cache is empty.
     *
     * @throws \RuntimeException If the refresh API fails or returns no token.
     */
    public function getAccessToken(): string
    {
        // Manual env override wins. Lets us debug with a known-good token
        // without hitting Transak's refresh endpoint.
        if ($this->manualOverride !== null && $this->manualOverride !== '') {
            return $this->manualOverride;
        }

        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $this->fetchNewAccessToken();
        Cache::put(self::CACHE_KEY, $token, self::CACHE_TTL_SECONDS);

        return $token;
    }

    /**
     * Drop the cached token. Call this from a higher layer (e.g. session
     * service) when Transak rejects a token mid-flight; the next call to
     * getAccessToken() will fetch a fresh one.
     */
    public function forgetCachedToken(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Hit the refresh endpoint and return the new access token.
     */
    private function fetchNewAccessToken(): string
    {
        if ($this->apiKey === '' || $this->apiSecret === '') {
            throw new RuntimeException(
                'TRANSAK_PUBLIC_KEY and TRANSAK_SECRET_KEY must both be set '
                . 'in .env before requesting a Transak access token.',
            );
        }

        $response = Http::baseUrl($this->authApiBase)
            ->withHeaders([
                'api-secret'   => $this->apiSecret,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->timeout(15)
            ->post(self::REFRESH_PATH, [
                'apiKey' => $this->apiKey,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'Transak refresh-token API returned %d: %s',
                $response->status(),
                $response->body(),
            ));
        }

        $token = $response->json('data.accessToken');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException(
                'Transak refresh-token API succeeded but returned no '
                . 'accessToken. Body: ' . $response->body(),
            );
        }

        return $token;
    }
}
