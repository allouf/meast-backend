<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsumerRequest extends FormRequest
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
            'name'    => 'required|string|max:255',
            'phone' => 'nullable|string|max:255|unique:users,phone',
            'email' => 'nullable|string|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
