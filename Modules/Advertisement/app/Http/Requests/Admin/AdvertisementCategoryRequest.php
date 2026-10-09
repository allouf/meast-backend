<?php

namespace Modules\Advertisement\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'slug' => 'nullable|string|max:255|unique:advertisement_categories,slug',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['slug'] = 'nullable|string|max:255|unique:advertisement_categories,slug,' . $this->route('id');
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.array' => 'The category name must be an array.',
            'name.*.required' => 'Each language name is required.',
            'slug.unique' => 'The slug has already been taken.',
        ];
    }
}
