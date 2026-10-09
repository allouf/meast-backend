<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class CouponRequest extends FormRequest
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
        $rules = [
            'store_id'             => 'required|exists:stores,id',
            'code'                 => 'required|string|max:50|unique:coupons,code',
            'discount_type'        => 'required|in:percentage,fixed',
            'discount_value'       => 'required|numeric|min:0',
            'min_cart_amount'      => 'nullable|numeric|min:0',
            'expires_at'           => 'required|date|after:now',
            'max_usage_count'      => 'nullable|integer|min:1',
            'max_usage_per_user'   => 'nullable|integer|min:1',
            'scope'                => 'required|in:all,categories,products',
            'categories'           => 'nullable|array',
            'categories.*'         => 'integer|exists:categories,id',
            'products'             => 'nullable|array',
            'products.*'           => 'integer|exists:products,id',
        ];

        // Additional condition based on scope value
        if ($this->input('scope') === 'categories') {
            $rules['categories'] = 'required|array|min:1';
            $rules['products'] = 'prohibited';
        }

        if ($this->input('scope') === 'products') {
            $rules['categories'] = 'prohibited';
            $rules['products'] = 'required|array|min:1';
            $rules['products.*'] = 'integer|exists:products,id';
        }

        if ($this->input('scope') === 'all') {
            $rules['category_id'] = 'prohibited';
            $rules['products'] = 'prohibited';
        }

        return $rules;
    }


    public function messages(): array
    {
        return [
            'code.unique'           => __('This coupon code is already in use.'),
            'expires_at.after'      => __('Expiration date must be in the future.'),
            'products.*.exists'     => __('One or more selected products are invalid.'),
            'categories.*.exists'       => __('One or more selected categories are invalid.'),

        ];
    }
}

