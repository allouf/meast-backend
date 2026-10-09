<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class GetStorePolicyRequest extends FormRequest
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
            'store_id' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'string', 'in:shipping,return,warranty'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'The store_id parameter is required.',
            'store_id.integer' => 'The store_id must be a valid number.',
            'store_id.min' => 'The store_id must be at least 1.',
            'type.required' => 'The type parameter is required.',
            'type.in' => 'Invalid policy type. Allowed types: shipping, return, warranty.',
        ];
    }
}

