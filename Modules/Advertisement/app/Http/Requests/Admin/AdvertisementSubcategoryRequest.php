<?php

namespace Modules\Advertisement\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'advertisement_category_id' => 'required|exists:advertisement_categories,id',
            'name' => 'required|array',
            'name.*' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:advertisement_subcategories,slug',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH') || ($this->isMethod('POST') && $this->route()?->getActionMethod() === 'update')) {
            $rules['advertisement_category_id'] = 'sometimes|required|exists:advertisement_categories,id';
            $rules['slug'] = 'nullable|string|max:255|unique:advertisement_subcategories,slug,' . $this->route('id');
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'advertisement_category_id.required' => 'The category is required.',
            'advertisement_category_id.exists' => 'The selected category does not exist.',
            'name.required' => 'The subcategory name is required.',
            'name.array' => 'The subcategory name must be an array.',
            'name.*.required' => 'Each language name is required.',
            'slug.unique' => 'The slug has already been taken.',
        ];
    }
}
