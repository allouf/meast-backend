<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobProfileRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:10000',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
