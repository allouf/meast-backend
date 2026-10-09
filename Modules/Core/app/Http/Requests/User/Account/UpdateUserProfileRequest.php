<?php

namespace Modules\Core\Http\Requests\User\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'max:255', Rule::unique('users', 'email')->ignore($this->id, 'id')],
            'phone' => ['nullable', 'string', Rule::unique('users', 'phone')->ignore($this->id, 'id')],
            'gender' => 'nullable|in:male,female',
            'birthday' => 'nullable|date',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png',
        ];
    }
}
