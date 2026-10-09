<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class DetachPackageItemsRequest extends FormRequest
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
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'exists:product_package_items,id'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'item_ids.required' => 'At least one item ID is required.',
            'item_ids.min' => 'At least one item ID is required.',
            'item_ids.*.exists' => 'One or more item IDs do not exist.',
        ];
    }
}

