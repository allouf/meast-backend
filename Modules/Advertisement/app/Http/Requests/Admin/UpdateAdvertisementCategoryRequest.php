<?php

namespace Modules\Advertisement\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdvertisementCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|array',
            'name.*' => 'required_with:name|string|max:255',
            'icon' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('advertisement_categories', 'slug')->ignore($this->route('id'))],
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.array' => 'The category name must be an array.',
            'name.*.required_with' => 'Each language name is required when name is present.',
            'slug.unique' => 'The slug has already been taken.',
        ];
    }
}
