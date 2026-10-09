<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = Auth::guard('api')->user();
        $type = $user && $user->hasRole('company') ? 'company' : 'individual';
        $this->merge(['type' => $type]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:individual,company',
            'display_name' => 'required|string|max:255',
            'language_ids' => 'nullable|array',
            'language_ids.*' => 'integer|exists:languages,id',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:10000',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'identity_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'verification_notes' => 'nullable|string|max:5000',
        ];
    }
}
