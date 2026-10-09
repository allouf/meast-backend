<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'price.numeric' => 'Variant price must be a number.',
            'price.min' => 'Variant price must be at least 0.',
            'stock.integer' => 'Variant stock must be an integer.',
            'stock.min' => 'Variant stock must be at least 0.',
            'low_stock_threshold.integer' => 'Low stock threshold must be an integer.',
            'low_stock_threshold.min' => 'Low stock threshold must be at least 0.',
            'image.mimes' => 'Variant image must be of type: jpg, jpeg, png, webp.',
            'image.max' => 'Variant image may not be greater than 10MB.',
        ];
    }
}


