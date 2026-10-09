<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class AddUserLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'max:255'],
            'items.*.label' => ['nullable', 'string', 'max:255'],
            'items.*.url' => ['required', 'url', 'max:255'],
        ];
    }
}

