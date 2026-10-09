<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;

class CompanyUpdateRequest extends FormRequest
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
            'name'                  => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $this->user()->id,
            'phone' => 'nullable|unique:users,phone,' . $this->user()->id,
            'photo'             => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'country_code'          => 'nullable|string|size:2',
            'region'                => 'nullable|string|max:255',
            'ref_code'              => 'nullable|string|max:20|unique:users,ref_code',
            'company_name'     => 'required|string|max:255',
            'registration_no'  => 'nullable|string|max:100',
            'vat_tax_id'       => 'nullable|string|max:100',
            'logo'             => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'trade_license'    => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
            'website'          => 'nullable|url|max:255',
        ];
    }
}
