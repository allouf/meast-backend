<?php

namespace Modules\Core\Http\Requests\User\Auth;

use Modules\Core\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
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
            'name' => 'required',
            'contact' => 'required|string',
            'password' => 'required|string|min:6',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'company_name' => 'required|string|max:255',
            'registration_no' => 'nullable|string|max:100',
            'vat_tax_id' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'trade_license' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'website' => 'nullable|url|max:255',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $contact = $this->input('contact');
            if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                if (User::where('email', $contact)->exists()) {
                    $validator->errors()->add('contact', 'This email is already taken.');
                }
            } else {
                if (User::where('phone', $contact)->exists()) {
                    $validator->errors()->add('contact', 'This phone number is already taken.');
                }
            }
        });
    }
}
