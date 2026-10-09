<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
        ];
    }
}

