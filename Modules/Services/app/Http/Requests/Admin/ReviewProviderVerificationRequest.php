<?php

namespace Modules\Services\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReviewProviderVerificationRequest extends FormRequest
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
            'verification_notes' => 'nullable|string|max:5000',
        ];
    }
}
