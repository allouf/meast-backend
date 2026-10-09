<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SendInvitationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'store_id' => 'required|exists:stores,id',
            'email' => 'required|email|max:255',
            'role' => 'required|in:STORE_OWNER,STORE_MANAGER,SUPPORT_AGENT',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'Store ID is required',
            'store_id.exists' => 'The selected store does not exist',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.max' => 'Email address cannot exceed 255 characters',
            'role.required' => 'Role is required',
            'role.in' => 'Invalid role selected. Must be one of: Store Owner, Store Manager, Support Agent',
        ];
    }


    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
