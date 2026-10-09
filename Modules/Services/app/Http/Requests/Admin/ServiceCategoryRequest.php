<?php

namespace Modules\Services\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ServiceCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');
        $rules = [
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.ar' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'requires_verification' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'boost_prohibited' => 'nullable|boolean',
        ];

        // Slug is only accepted on update; on create it is generated from name.
        if ($id) {
            $rules['slug'] = 'nullable|string|max:255|unique:service_categories,slug,' . $id;
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('services::messages.service_categories.name_required'),
            'name.unique' => __('services::messages.service_categories.name_unique'),
            'slug.unique' => __('services::messages.service_categories.slug_unique'),
        ];
    }
}
