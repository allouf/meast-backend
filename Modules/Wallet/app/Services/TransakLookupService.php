<?php

namespace Modules\Wallet\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Wraps Transak's PUBLIC lookup endpoints — countries, supported crypto/fiat
 * currencies, and live quotes.
 *
 * These endpoints don't require auth and don't accept user PII. We call
 * them server-side rather than from the frontend for two reasons:
 *
 *   1. CACHING. Country lists, supported currencies, and payment-method
 *      catalogs change rarely (days/weeks). Caching them server-side
 *      avoids hitting Transak on every page render.
 *   2. CONTRACT. We can shape the response to whatever our frontend wants
 *      without the frontend caring about Transak-specific field names.
 *
 * NOTE on quotes: quotes are NOT cached — Transak refreshes pricing every
 * minute and a stale quote would lead to slippage/refusal at order time.
 */
class TransakLookupService
{
    /** Cache TTL for catalog data (countries, currencies). 1 hour is plenty. */
    private const CATALOG_TTL_SECONDS = 3600;

    public function __construct(
        private readonly string $apiBase,
        private readonly string $publicKey,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCountries(): array
    {
        return Cache::remember(
            'transak:countries',
            self::CATALOG_TTL_SECONDS,
            fn () => $this->client()
                ->get('/api/v2/lookup/countries')
                ->json('data.countries', []),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCryptoCurrencies(): array
    {
        return Cache::remember(
            'transak:crypto-currencies',
            self::CATALOG_TTL_SECONDS,
            fn () => $this->client()
                ->get('/api/v2/lookup/currencies/crypto-currencies')
                ->json('data.cryptoCurrencies', []),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getFiatCurrencies(): array
    {
        return Cache::remember(
            'transak:fiat-currencies',
            self::CATALOG_TTL_SECONDS,
            fn () => $this->client()
                ->get('/api/v2/lookup/currencies/fiat-currencies')
                ->json('data.fiatCurrencies', []),
        );
    }

    /**
     * Fetch a live BUY/SELL quote.
     *
     * Pass the validated request inputs (fiatCurrency, cryptoCurrency,
     * isBuyOrSell, network, paymentMethod, plus optionally fiatAmount or
     * cryptoAmount). The apiKey is injected here.
     *
     * @param  array<string, scalar>  $params
     * @return array<string, mixed>
     */
    public function getQuote(array $params): array
    {
        $params['apiKey'] = $this->publicKey;

        return $this->client()
            ->get('/api/v2/lookup/quotes', $params)
            ->json('data', []);
    }

    /**
     * One configured Http client per call. We don't share state and the
     * underlying Guzzle client is cheap to construct.
     */
    private function client(): PendingRequest
    {
        return Http::baseUrl($this->apiBase)
            ->acceptJson()
            ->timeout(15);
    }
}
