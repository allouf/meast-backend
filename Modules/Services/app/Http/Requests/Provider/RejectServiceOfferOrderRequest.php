<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class RejectServiceOfferOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_id' => ['required', 'exists:service_offer_order_rejection_reasons,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
