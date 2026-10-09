<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class FlashSaleRequest extends FormRequest
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
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'max_per_user' => 'nullable|integer|min:1',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.variants' => 'required|array|min:1',
            'products.*.variants.*' => 'exists:product_variants,id',
        ];
    }

    public function messages(): array
    {
        return [
            'store_id.required' => 'Store ID is required.',
            'store_id.exists' => 'The selected store does not exist.',
            'name.required' => 'Name is required.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.after' => 'End date must be after start date.',
            'discount_type.in' => 'Discount type must be percentage or fixed.',
            'products.required' => 'At least one product is required.',
            'products.*.variants.required' => 'At least one variant is required for each product.',
            'products.*.variants.*.exists' => 'One or more variant IDs are invalid.',
        ];
    }
}

