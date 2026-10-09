<?php

namespace Modules\Store\Http\Requests\User;

use Modules\Store\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'discount_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
                'required_without:fixed_discount',
                function ($attribute, $value, $fail) {
                    if ($this->filled('fixed_discount')) {
                        $fail('Cannot set both discount_percent and fixed_discount.');
                    }
                }
            ],
            'fixed_discount' => [
                'nullable',
                'numeric',
                'min:0',
                'required_without:discount_percent',
                function ($attribute, $value, $fail) {
                    if ($this->filled('discount_percent')) {
                        $fail('Cannot set both discount_percent and fixed_discount.');
                    }
                }
            ],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.variant_id' => [
                'nullable',
                'integer',
                'exists:product_variants,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $productId = $this->input(str_replace('.variant_id', '.product_id', $attribute));
                        if ($productId && !ProductVariant::where('id', $value)
                            ->where('product_id', $productId)
                            ->exists()) {
                            $fail('The variant does not belong to the specified product.');
                        }
                    }
                }
            ],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Package title is required.',
            'title.max' => 'Package title may not be greater than 255 characters.',
            'discount_percent.numeric' => 'Discount percent must be a number.',
            'discount_percent.min' => 'Discount percent must be at least 0.',
            'discount_percent.max' => 'Discount percent cannot exceed 100.',
            'fixed_discount.numeric' => 'Fixed discount must be a number.',
            'fixed_discount.min' => 'Fixed discount must be at least 0.',
            'items.min' => 'Package must have at least one item.',
            'items.*.product_id.exists' => 'One or more products do not exist.',
            'items.*.variant_id.exists' => 'One or more variants do not exist.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
