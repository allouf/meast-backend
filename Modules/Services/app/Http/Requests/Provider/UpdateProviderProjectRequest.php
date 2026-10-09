<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProjectRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'url' => 'nullable|string|url|max:500',
            'description' => 'nullable|string|max:10000',
            'sort_order' => 'nullable|integer|min:0',
            'service_offer_ids' => 'nullable|array',
            'service_offer_ids.*' => 'integer|exists:service_offers,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
