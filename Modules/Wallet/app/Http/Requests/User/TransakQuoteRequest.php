<?php

namespace Modules\Wallet\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates inputs for GET /api/v1/transak/quote.
 *
 * Either fiatAmount or cryptoAmount must be present — the user is either
 * specifying "I want to spend $50" or "I want to receive 50 USDC."
 */
class TransakQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth handled by route middleware (auth:api).
        return true;
    }

    public function rules(): array
    {
        return [
            'fiatCurrency'   => ['required', 'string', 'size:3'],
            'cryptoCurrency' => ['required', 'string', 'max:32'],
            'isBuyOrSell'    => ['required', 'in:BUY,SELL'],
            'network'        => ['required', 'string', 'max:32'],
            'paymentMethod'  => ['required', 'string', 'max:64'],
            'fiatAmount'     => ['nullable', 'numeric', 'min:0', 'required_without:cryptoAmount'],
            'cryptoAmount'   => ['nullable', 'numeric', 'min:0', 'required_without:fiatAmount'],
        ];
    }
}
