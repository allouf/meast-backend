<?php

namespace App\Http\Requests\User\Account;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required',
            'string',
            'max:255',
            Rule::unique('users', 'email')->ignore($this->id, 'id'),
            'phone' => 'nullable|string',
            Rule::unique('users', 'phone')->ignore($this->id, 'id'),

            'gender' => 'nullable|in:male,female',
            'birthday' => 'nullable|date',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png',
        ];
    }
}
