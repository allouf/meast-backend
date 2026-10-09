<?php

namespace Modules\Job\Http\Requests\Admin\Job;

use Modules\Job\Models\JobTemplateField;
use Illuminate\Foundation\Http\FormRequest;

class JobTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = (bool) $this->route('id');

        $rules = [
            'title' => $isUpdate ? 'sometimes|required|string|max:255' : 'required|string|max:255',
            'category_id' => $isUpdate ? 'sometimes|required|exists:job_categories,id' : 'required|exists:job_categories,id',
            'employer_id' => 'nullable|exists:users,id',
            'is_system_template' => $isUpdate ? 'nullable|boolean' : 'prohibited',
            'is_active' => 'nullable|boolean',
            'fields' => $isUpdate ? 'sometimes|array|min:1' : 'required|array|min:1',
            'fields.*.label' => ($isUpdate ? 'required_with:fields|' : 'required|') . 'string|max:255',
            'fields.*.type' => ($isUpdate ? 'required_with:fields|' : 'required|') . 'string|in:' . implode(',', JobTemplateField::$types),
            'fields.*.validation_rules' => 'nullable|array',
            'fields.*.options' => 'nullable|array',
            'fields.*.sort_order' => 'nullable|integer|min:0',
            'fields.*.is_required' => 'nullable|boolean',
        ];

        return $rules;
    }
}
