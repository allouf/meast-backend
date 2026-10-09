<?php

namespace Modules\Job\Http\Requests\Admin\Job;

use Illuminate\Foundation\Http\FormRequest;

class SkillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $rules = [
            'name' => 'required|string|max:255|unique:skills,name',
            'slug' => 'nullable|string|max:255|unique:skills,slug',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];

        if ($id && ($this->isMethod('PUT') || $this->isMethod('PATCH') || ($this->isMethod('POST') && $this->route()->getActionMethod() === 'update'))) {
            $rules['name'] = 'required|string|max:255|unique:skills,name,' . $id;
            $rules['slug'] = 'nullable|string|max:255|unique:skills,slug,' . $id;
        }

        return $rules;
    }
}
