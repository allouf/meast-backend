<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class AddUserWorkExperienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.company_name' => ['required', 'string', 'max:255'],
            'items.*.job_title' => ['required', 'string', 'max:255'],
            'items.*.industry' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.start_date' => ['required', 'date'],
            'items.*.end_date' => ['nullable', 'date'],
            'items.*.is_current' => ['nullable', 'boolean'],
        ];
    }
}

