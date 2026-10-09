<?php

namespace Modules\Job\Http\Requests\Admin\Job;

use Illuminate\Foundation\Http\FormRequest;

class JobCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $rules = [
            'name' => 'required|string|max:255|unique:job_categories,name',
            'slug' => 'nullable|string|max:255|unique:job_categories,slug',
            'description' => 'nullable|string|max:65535',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        if ($id && ($this->isMethod('PUT') || $this->isMethod('PATCH') || ($this->isMethod('POST') && $this->route()->getActionMethod() === 'update'))) {
            $rules['name'] = 'required|string|max:255|unique:job_categories,name,' . $id;
            $rules['slug'] = 'nullable|string|max:255|unique:job_categories,slug,' . $id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('job::messages.job_categories.name_required'),
            'name.unique' => __('job::messages.job_categories.name_unique'),
            'slug.unique' => __('job::messages.job_categories.slug_unique'),
        ];
    }
}
