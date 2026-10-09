<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'max:255'],
            'level' => ['required', 'integer', 'between:1,10'],
        ];
    }
}

