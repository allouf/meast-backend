<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderCustomRequestQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proposed_budget' => ['required', 'numeric', 'min:0'],
            'cover_letter_or_message' => ['required', 'string', 'max:10000'],
        ];
    }
}
