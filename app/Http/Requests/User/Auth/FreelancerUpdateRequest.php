<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;

class FreelancerUpdateRequest extends FormRequest
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
            'email' => 'sometimes|email|unique:users,email,' . $this->user()->id,
            'phone' => 'sometimes|phone|unique:users,phone,' . $this->user()->id,
            'password'              => 'required|string|min:6',
            'country_code'          => 'nullable|string|size:2',
            'region'                => 'nullable|string|max:255',
            'ref_code'              => 'nullable|string|max:20|unique:users,ref_code',
            'profession_id'         => 'required|exists:professions,id',
            'national_id'           => 'required|string|max:50',
            'portfolio_url'         => 'nullable|url|max:255',
            'documents'    => 'nullable|array',
            'documents.*'  => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }
}
