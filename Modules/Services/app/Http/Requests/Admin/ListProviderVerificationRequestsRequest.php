<?php

namespace Modules\Services\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\ProviderVerificationStatus;

class ListProviderVerificationRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verification_status' => ['nullable', 'string', Rule::in(ProviderVerificationStatus::values())],
            'search' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:individual,company',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}
