<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Services\Repositories\Interfaces\ServiceProviderRepositoryInterface;

class SubmitProviderVerificationRequest extends FormRequest
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
            'identity_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'verification_notes' => 'nullable|string|max:5000',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('identity_document')) {
                return;
            }

            $user = $this->user('api');
            if (! $user) {
                return;
            }

            $profile = app(ServiceProviderRepositoryInterface::class)->getByUserId($user->id);
            if (! $profile || empty($profile->identity_document)) {
                $validator->errors()->add(
                    'identity_document',
                    __('services::messages.service_provider.verification_identity_required')
                );
            }
        });
    }
}
