<?php

namespace Modules\Wallet\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Wallet\Http\Requests\User\TransakSignUrlRequest;
use Modules\Wallet\Services\TransakSessionService;

/**
 * POST /api/v1/transak/sign-url
 *
 * Despite the legacy "sign-url" name, this endpoint no longer signs URLs
 * locally. It calls Transak's session API:
 *
 *   POST {transak_api}/api/v2/auth/session
 *   Headers: access-token: <Partner Access Token>
 *   Body:    { widgetParams: { apiKey, referrerDomain, ... } }
 *
 * Transak stores the widget params server-side and returns a one-shot
 * widgetUrl (single use, 5-minute expiry). The frontend opens that URL
 * directly — there's no signature for them to handle.
 *
 * Why we kept the route name `sign-url`: any frontend code already wired
 * up against it keeps working. We just changed the implementation behind
 * it. Response shape (`widget_url`, `partner_order_id`, `environment`)
 * stays the same.
 *
 * Constraints:
 *   - The partner backend's outbound IP MUST be whitelisted in the
 *     Transak partner dashboard (Transak rejects calls from non-whitelisted
 *     IPs with a 403 / 401).
 *   - The widgetUrl returned is single-use. The frontend should call this
 *     endpoint right before opening the widget — never cache the URL.
 */
class TransakSignController extends Controller
{
    public function __construct(
        protected TransakSessionService $session,
    ) {
    }

    public function signUrl(TransakSignUrlRequest $request)
    {
        try {
            $userId = Auth::id();
            $validated = $request->validated();
            $partnerOrderId = (string) Str::uuid();

            // Build the widget params Transak's session API will receive.
            // apiKey and referrerDomain are auto-injected inside the service —
            // we don't pass them here.
            $widgetParams = [
                'environment'         => $this->widgetEnvironment(),
                'walletAddress'       => $validated['walletAddress'],
                'cryptoCurrencyCode'  => $validated['cryptoCurrencyCode'],
                'network'             => $validated['network'],
                'fiatCurrency'        => $validated['fiatCurrency']
                    ?? config('services.transak.default_fiat_currency'),
                'fiatAmount'          => $validated['fiatAmount'] ?? null,
                'email'               => $validated['email'] ?? null,
                // Our internal references — echoed back on webhooks.
                'partnerCustomerId'   => (string) $userId,
                'partnerOrderId'      => $partnerOrderId,
                // Where Transak sends the user when the widget completes.
                'redirectURL'         => $validated['redirectURL']
                    ?? config('services.transak.redirect_url'),
                // Lock the user to BUY — the bridge is one-way for our use case.
                'productsAvailed'     => 'BUY',
                // Keep the wallet address read-only inside the widget.
                'disableWalletAddressForm' => 'true',
            ];

            $session = $this->session->createSession($widgetParams);

            return $this->successResponse(
                __('wallet::messages.transak_sign_url_success'),
                [
                    'widget_url'        => $session['widget_url'],
                    'partner_order_id'  => $partnerOrderId,
                    'environment'       => $this->widgetEnvironment(),
                ],
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /** Transak expects 'STAGING' or 'PRODUCTION'. */
    private function widgetEnvironment(): string
    {
        return config('services.transak.environment') === 'production'
            ? 'PRODUCTION'
            : 'STAGING';
    }
}
