<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BetaRegistrationRequest extends FormRequest
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
        $registeringAs = $this->input('registering_as');

        // If registering_as is provided (business user), validate all business fields
        if ($registeringAs) {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:255|unique:users,phone',
                'registering_as' => [
                    'required',
                    Rule::in([
                        'Store Owner / Seller',
                        'Brand / Manufacturer',
                        'Service Provider',
                        'Investor / Partner',
                        'Explorer'
                    ])
                ],
                'country' => 'required|string|max:255',
                'business_type' => [
                    'required',
                    Rule::in([
                        'Individual',
                        'Small business',
                        'Medium company',
                        'Enterprise / Distributor'
                    ])
                ],
                'industry_categories' => 'required|array',
                'industry_categories.*' => 'string|max:255',
                'selling_online' => [
                    'required',
                    Rule::in([
                        'offline_only',
                        'social_media',
                        'own_website',
                        'marketplaces'
                    ])
                ],
                'interested_in' => 'required|array',
                'interested_in.*' => [
                    Rule::in([
                        'store_setup',
                        'pricing',
                        'community',
                        'cross_border',
                        'payments',
                        'analytics',
                        'others'
                    ])
                ],
                'structured_feedback' => [
                    'required',
                    Rule::in(['forms', 'calls', 'maybe', 'no'])
                ],
            ];
        }

        // Normal user - only country, interested_in, structured_feedback
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('beta_registrations', 'email'),
                Rule::unique('users', 'email'),
            ],
            'phone' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone')],
            'country' => 'required|string|max:255',
            'interested_in' => 'required|array',
            'interested_in.*' => [
                Rule::in([
                    'store_setup',
                    'pricing',
                    'community',
                    'cross_border',
                    'payments',
                    'analytics',
                    'others'
                ])
            ],
            'structured_feedback' => [
                'required',
                Rule::in(['forms', 'calls', 'maybe', 'no'])
            ],
        ];
    }
}
