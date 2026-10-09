<?php

namespace Modules\Store\Http\Requests\Seller;

use Modules\Store\Enums\ProductStatus;
use Modules\Store\Models\Category;
use Modules\Store\Models\ProductVariant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
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
            'store_id' => [
                'required',
                'integer',
                'exists:stores,id',
            ],
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    $category = Category::find($value);
                    if (!$category) {
                        $fail('The selected category does not exist.');
                        return;
                    }
                    if (isset($category->is_active) && !$category->is_active) {
                        $fail('The selected category is not active.');
                    }
                }
            ],

            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'specifications' => ['nullable', 'string'],
            'additional_features' => ['nullable', 'array'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'discount_value' => [
                'nullable',
                'numeric',
                'min:0',
                'required_with:discount_type',
                function ($attribute, $value, $fail) {
                    if (request('discount_type') === 'percentage' && $value > 100) {
                        $fail('Discount percentage cannot exceed 100%.');
                    }
                }
            ],
            'discount_type' => [
                'nullable',
                'string',
                Rule::in(['percentage', 'fixed']),
                'required_with:discount_value'
            ],

            'status' => [
                'required',
                'string',
                Rule::in([
                    ProductStatus::ACTIVE->value,
                    ProductStatus::DRAFT->value,
                ]),
            ],

            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,avi,mov', 'max:10240'],
            'options' => ['nullable', 'array'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.type' => ['nullable', 'string', Rule::in(['text', 'number', 'color'])],
            'options.*.values' => ['required_with:options', 'array', 'min:1'],
            'options.*.values.*' => ['required', 'string', 'max:255'],
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($this->route('id')) {
                        return;
                    }
                    if ($value && ProductVariant::where('sku', $value)->exists()) {
                        $fail("The SKU '$value' has already been taken.");
                    }
                }
            ],
            'variants.*.price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.option_values' => ['nullable', 'array'],
            'variants.*.option_values.*' => ['required_with:variants.*.option_values', 'string', 'max:255'],
        ];
    }
    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'Store is required.',
            'store_id.exists' => 'The selected store does not exist.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'The selected category does not exist.',
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name may not be greater than 255 characters.',
            'price.numeric' => 'Product price must be a number.',
            'price.min' => 'Product price must be at least 0.',
            'variants.*.price.required_with' => 'Variant price is required when variants are provided.',

            'status.required' => 'Product status is required.',
            'status.in' => 'Product status must be either active or draft.',

            'gallery.*.mimes' => 'Gallery files must be of type: jpg, jpeg, png, mp4, avi, mov, svg.',
            'gallery.*.max' => 'Gallery files may not be greater than 10MB.',
            'options.*.name.required_with' => 'Option name is required when options are provided.',
            'options.*.values.required_with' => 'Option values are required when options are provided.',
            'options.*.values.min' => 'Each option must have at least one value.',
            'variants.*.sku.max' => 'Variant SKU may not be greater than 255 characters.',
            'variants.*.price.numeric' => 'Variant price must be a number.',
            'variants.*.price.min' => 'Variant price must be at least 0.',
            'variants.*.stock.integer' => 'Variant stock must be an integer.',
            'variants.*.stock.min' => 'Variant stock must be at least 0.',
            'variants.*.stock.required_with' => 'Stock is required for each variant.',
            'variants.*.image.mimes' => 'Variant image must be of type: jpg, jpeg, png, webp.',
            'variants.*.image.max' => 'Variant image may not be greater than 10MB.',
        ];
    }
}
