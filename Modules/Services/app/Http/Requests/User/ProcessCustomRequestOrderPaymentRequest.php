<?php

namespace Modules\Services\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessCustomRequestOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                'max:64',
                Rule::in(['credit_card', 'mada', 'apple_pay']),
            ],
        ];
    }
}
