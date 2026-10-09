<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        // Get the user ID from route (works for consumers/update/{id} and companies/update/{id})
        $userId = $this->route('id') ?? $this->route()->parameter('id') ?? null;
        $userId = $userId !== null && $userId !== '' ? (int) $userId : null;

        return [
            'name'             => 'sometimes|string|max:255',
            'email'            => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                $userId !== null ? Rule::unique('users', 'email')->ignore($userId, 'id') : 'unique:users,email',
            ],
            'phone'            => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                $userId !== null ? Rule::unique('users', 'phone')->ignore($userId, 'id') : 'unique:users,phone',
            ],
            'password'         => 'nullable|string|min:6|confirmed',
            'country_code'     => 'nullable|string|size:2',
            'region'           => 'nullable|string|max:255',
            'company_name'     => 'sometimes|required_if:role,company|string|max:255',
            'registration_no'  => 'sometimes|required_if:role,company|string|max:255',
            'vat_tax_id'       => 'nullable|string|max:255',
            'website'          => 'nullable|url',
            'logo'             => 'nullable|image|max:2048',
            'trade_license'    => 'nullable|image|max:2048',
        ];
    }
}
