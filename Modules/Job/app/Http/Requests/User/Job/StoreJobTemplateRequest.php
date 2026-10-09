<?php

namespace Modules\Job\Http\Requests\User\Job;

use Modules\Job\Models\JobTemplateField;
use Illuminate\Foundation\Http\FormRequest;

class StoreJobTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => 'required|string|in:' . implode(',', JobTemplateField::$types),
            'fields.*.validation_rules' => 'nullable|array',
            'fields.*.options' => 'nullable|array',
            'fields.*.sort_order' => 'nullable|integer|min:0',
            'fields.*.is_required' => 'nullable|boolean',
        ];
    }
}
