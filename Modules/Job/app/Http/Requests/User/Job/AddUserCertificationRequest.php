<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class AddUserCertificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.issuer' => ['nullable', 'string', 'max:255'],
            'items.*.issue_date' => ['nullable', 'date'],
            'items.*.expiration_date' => ['nullable', 'date'],
            'items.*.credential_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}

