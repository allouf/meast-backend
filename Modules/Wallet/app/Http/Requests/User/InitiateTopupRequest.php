<?php

namespace Modules\Wallet\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class InitiateTopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication is enforced by the route's auth:api middleware.
        // No additional row-level authorization needed — every logged-in user
        // can top up their own wallet, period.
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'min:' . (float) config('services.stripe.topup_min', 1),
                'max:' . (float) config('services.stripe.topup_max', 1000),
                // Two decimal places — Stripe rejects fractional cents anyway.
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            // Optional rail hint. Frontend can pass 'stripe' for a card
            // top-up or 'nowpayments' for a crypto top-up. If omitted, the
            // RailResolver falls back to its registration-order default
            // (currently Stripe). Other registered rails (stripe_connect,
            // platform_credit) are intentionally excluded — neither is a
            // valid top-up flow.
            'preferred_rail' => 'nullable|string|in:stripe,nowpayments',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.regex'        => 'The amount must have at most 2 decimal places.',
            'preferred_rail.in'   => 'The preferred rail must be one of: stripe, nowpayments.',
        ];
    }
}
