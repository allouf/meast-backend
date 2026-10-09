<?php

namespace App\Http\Requests\User\Account;

use Illuminate\Foundation\Http\FormRequest;

class AddUserAddressRequest extends FormRequest
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
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address' => [
                'required',
                'string',
                'max:1000',
            ],
            'city' => [
                'required',
                'string',
                'max:255',
            ],
            'country' => [
                'required',
                'string',
                'max:255',
            ],
            'country_code' => [
                'required',
                'string',
                'size:2',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'region' => [
                'nullable',
                'string',
                'max:255',
            ],
            'lat' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'lng' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'is_default' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'address.required' => 'Address is required.',
            'address.max' => 'Address cannot exceed 1000 characters.',
            'city.required' => 'City is required.',
            'city.max' => 'City cannot exceed 255 characters.',
            'country.required' => 'Country is required.',
            'country.max' => 'Country cannot exceed 255 characters.',
            'country_code.required' => 'Country code is required.',
            'country_code.size' => 'Country code must be exactly 2 characters.',
            'postal_code.max' => 'Postal code cannot exceed 20 characters.',
            'region.max' => 'Region cannot exceed 255 characters.',
            'lat.required' => 'Latitude is required.',
            'lat.numeric' => 'Latitude must be a valid number.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.required' => 'Longitude is required.',
            'lng.numeric' => 'Longitude must be a valid number.',
            'lng.between' => 'Longitude must be between -180 and 180.',
        ];
    }
}
