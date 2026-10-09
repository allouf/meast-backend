<?php

namespace App\Http\Requests\User\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConsumerRequest extends FormRequest
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
            'contact'   => 'required|string',
            'password'              => 'required|string|min:6',
            'country_code'          => 'nullable|string|size:2',
            'region'                => 'nullable|string|max:255',
            'ref_code'              => 'nullable|string|max:20|unique:users,ref_code',
        ];
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $contact = $this->input('contact');

            if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                if (\App\Models\User::where('email', $contact)->exists()) {
                    $validator->errors()->add('contact', 'This email is already taken.');
                }
            } else {
                if (\App\Models\User::where('phone', $contact)->exists()) {
                    $validator->errors()->add('contact', 'This phone number is already taken.');
                }
            }
        });
    }
}
