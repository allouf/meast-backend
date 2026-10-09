<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
            'password' => 'required|min:8',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png',
            'trade_log' => 'nullable|file|mimes:pdf,jpg,jpeg,png',
        ];
    }
}
