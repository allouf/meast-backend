<?php

namespace Modules\Store\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
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
            'user_address_id' => [
                'required',
                'integer',
                'exists:user_addresses,id',
            ],
            'first_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'items' => [
                'nullable',
                'array',
                'min:1',
            ],
            'items.*.product_variant_id' => [
                'required_with:items',
                'integer',
                'exists:product_variants,id',
            ],
            'items.*.quantity' => [
                'required_with:items',
                'integer',
                'min:1',
                'max:999',
            ],

            'packages' => [
                'nullable',
                'array',
                'min:1',
            ],

            'packages.*.package_id' => [
                'required_with:packages',
                'integer',
                'exists:product_packages,id',
            ],

            'packages.*.quantity' => [
                'required_with:packages',
                'integer',
                'min:1',
                'max:999',
            ],

            'payment_method' => [
                'nullable',
                'string',
                'in:paypal,stripe,cod,bank_transfer',
            ],
            'shipping_method' => [
                'nullable',
                'string',
                'in:standard,express,pickup',
            ],
            'coupon_code' => [
                'nullable',
                'string',
                'exists:coupons,code',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            $packages = $this->input('packages', []);

            if (empty($items) && empty($packages)) {
                $validator->errors()->add(
                    'order',
                    'Order must contain at least one item or one package.'
                );
            }
        });
    }
    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'user_address_id.required' => 'Shipping address is required.',
            'user_address_id.exists' => 'The selected shipping address does not exist.',
            'items.required' => 'Order items are required.',
            'items.array' => 'Items must be an array.',
            'items.min' => 'At least one item is required.',
            'items.*.product_variant_id.required' => 'Product variant ID is required for each item.',
            'items.*.product_variant_id.exists' => 'One or more product variants do not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.integer' => 'Quantity must be an integer.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Quantity cannot exceed 999.',
            'packages.array' => 'Packages must be an array.',
            'packages.min' => 'At least one package is required.',
            'packages.*.package_id.required_with' => 'Package ID is required for each package.',
            'packages.*.package_id.exists' => 'One or more packages do not exist.',
            'packages.*.quantity.required_with' => 'Quantity is required for each package.',
            'packages.*.quantity.integer' => 'Quantity must be an integer.',
            'packages.*.quantity.min' => 'Quantity must be at least 1.',
            'packages.*.quantity.max' => 'Quantity cannot exceed 999.',
            'payment_method.in' => 'The payment method must be one of: paypal, stripe, cod, bank_transfer.',
            'shipping_method.in' => 'The shipping method must be one of: standard, express, pickup.',
            'coupon_code.exists' => 'The coupon code does not exist.',
        ];
    }
}
