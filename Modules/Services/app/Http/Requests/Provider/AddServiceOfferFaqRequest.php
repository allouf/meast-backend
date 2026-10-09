<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class AddServiceOfferFaqRequest extends FormRequest
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
            'question' => 'required|string|max:1000',
            'answer' => 'required|string|max:5000',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
