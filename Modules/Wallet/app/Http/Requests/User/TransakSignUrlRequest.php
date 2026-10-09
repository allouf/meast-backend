<?php

namespace Modules\Wallet\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates inputs for POST /api/v1/transak/sign-url.
 *
 * Frontend sends:
 *   - walletAddress       — destination for the bought crypto (required)
 *   - cryptoCurrencyCode  — e.g. "ETH", "USDC", "MEEC"
 *   - network             — e.g. "ethereum", "polygon"
 *   - fiatCurrency        — optional, defaults to services.transak.default_fiat_currency
 *   - fiatAmount          — optional pre-fill amount
 *   - email               — optional, pre-fills the user's KYC email in widget
 *   - redirectURL         — optional override of the post-checkout redirect
 */
class TransakSignUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'walletAddress'      => ['required', 'string', 'max:128'],
            'cryptoCurrencyCode' => ['required', 'string', 'max:32'],
            'network'            => ['required', 'string', 'max:32'],
            'fiatCurrency'       => ['nullable', 'string', 'size:3'],
            'fiatAmount'         => ['nullable', 'numeric', 'min:0'],
            'email'              => ['nullable', 'email'],
            'redirectURL'        => ['nullable', 'url'],
        ];
    }
}
