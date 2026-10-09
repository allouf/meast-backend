<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class AddUserProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.project_url' => ['nullable', 'url', 'max:255'],
            'items.*.start_date' => ['nullable', 'date'],
            'items.*.end_date' => ['nullable', 'date'],
        ];
    }
}

