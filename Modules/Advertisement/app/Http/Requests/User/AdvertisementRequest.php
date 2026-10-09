<?php

namespace Modules\Advertisement\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'advertisement_category_id' => 'required|exists:advertisement_categories,id',
            'advertisement_subcategory_id' => 'nullable|exists:advertisement_subcategories,id',
            'description' => 'required|string|max:10000',
            'price' => ['nullable', 'numeric', 'min:0', function ($attribute, $value, $fail) {
                if ($this->boolean('is_free') && $value !== null && (float) $value != 0) {
                    $fail(__('advertisement::validation.advertisement.price_must_be_zero_when_free'));
                }
            }],
            'is_price_negotiable' => 'nullable|boolean',
            'is_free' => 'nullable|boolean',
            'condition' => 'required|in:new,used',
            'contact_method' => 'required|in:chat,phone,both',
            'phone' => 'nullable|string|max:20',
            'show_phone' => 'nullable|boolean',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'attributes' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The advertisement title is required.',
            'title.max' => 'The advertisement title cannot exceed 255 characters.',
            'advertisement_category_id.required' => 'The category is required.',
            'advertisement_category_id.exists' => 'The selected category is invalid.',
            'advertisement_subcategory_id.exists' => 'The selected subcategory is invalid.',
            'description.required' => 'The description is required.',
            'description.max' => 'The description cannot exceed 10000 characters.',
            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price must be at least 0.',
            'condition.required' => 'The condition is required.',
            'condition.in' => 'The condition must be either new or used.',
            'contact_method.required' => 'The contact method is required.',
            'contact_method.in' => 'The contact method must be chat, phone, or both.',
            'images.required' => 'At least one image is required.',
            'images.min' => 'At least one image is required.',
            'images.max' => 'You can upload a maximum of 10 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif, webp.',
            'images.*.max' => 'Each image size cannot exceed 2MB.',
        ];
    }
}
