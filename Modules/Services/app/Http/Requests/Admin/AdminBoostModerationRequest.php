<?php

namespace Modules\Services\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminBoostModerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
