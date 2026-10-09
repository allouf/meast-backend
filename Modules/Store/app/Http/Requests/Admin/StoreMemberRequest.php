<?php

namespace Modules\Store\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
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
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', 'in:STORE_OWNER,STORE_MANAGER,SUPPORT_AGENT'],
            'status' => ['nullable', 'in:invited,active,revoked'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'Store is required',
            'store_id.exists' => 'Selected store does not exist',
            'user_id.required' => 'User is required',
            'user_id.exists' => 'Selected user does not exist',
            'role.required' => 'Role is required',
            'role.in' => 'Invalid role selected. Must be one of: STORE_OWNER, STORE_MANAGER, SUPPORT_AGENT',
            'status.in' => 'Invalid status selected. Must be one of: invited, active, revoked',
        ];
    }
}
