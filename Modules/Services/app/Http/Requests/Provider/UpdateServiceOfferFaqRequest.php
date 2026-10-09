<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceOfferFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => 'sometimes|string|max:1000',
            'answer' => 'sometimes|string|max:5000',
            'sort_order' => 'sometimes|nullable|integer|min:0',
        ];
    }
}
