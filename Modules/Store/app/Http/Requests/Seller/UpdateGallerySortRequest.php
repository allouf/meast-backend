<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGallerySortRequest extends FormRequest
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
            'gallery' => ['required', 'array', 'min:1'],
            'gallery.*.id' => ['required', 'integer', 'exists:product_galleries,id'],
            'gallery.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'gallery.required' => 'Gallery sort data is required.',
            'gallery.min' => 'At least one gallery item is required.',
            'gallery.*.id.required' => 'Each gallery item must have an id.',
            'gallery.*.id.exists' => 'One or more gallery items do not exist.',
            'gallery.*.sort_order.required' => 'Each gallery item must have a sort_order.',
            'gallery.*.sort_order.integer' => 'Sort order must be an integer.',
            'gallery.*.sort_order.min' => 'Sort order must be at least 0.',
        ];
    }
}

