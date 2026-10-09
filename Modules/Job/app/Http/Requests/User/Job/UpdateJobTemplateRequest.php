<?php

namespace Modules\Job\Http\Requests\User\Job;

use Modules\Job\Models\JobTemplateField;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'is_active' => 'nullable|boolean',
            'fields' => 'sometimes|array|min:1',
            'fields.*.label' => 'required_with:fields|string|max:255',
            'fields.*.type' => 'required_with:fields|string|in:' . implode(',', JobTemplateField::$types),
            'fields.*.validation_rules' => 'nullable|array',
            'fields.*.options' => 'nullable|array',
            'fields.*.sort_order' => 'nullable|integer|min:0',
            'fields.*.is_required' => 'nullable|boolean',
        ];
    }
}
