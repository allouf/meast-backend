<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class AddUserEducationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.degree_type' => ['required', 'string', 'in:diploma,bachelor,master,phd,certificate,other'],
            'items.*.university_name' => ['required', 'string', 'max:255'],
            'items.*.major' => ['nullable', 'string', 'max:255'],
            'items.*.start_date' => ['nullable', 'date'],
            'items.*.end_date' => ['nullable', 'date'],
            'items.*.expected_graduation_date' => ['nullable', 'date'],
            'items.*.is_current' => ['nullable', 'boolean'],
            'items.*.description' => ['nullable', 'string'],
        ];
    }
}

