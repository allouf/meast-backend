<?php

namespace App\Http\Requests\Admin\General;

use Illuminate\Foundation\Http\FormRequest;

class ReferralMilestoneRequest extends FormRequest
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
            'name' => 'required|array', // تأكد من إرسال name كـ JSON يحتوي en, ar
            'name.en' => 'required|string|max:255',
            'name.ar' => 'required|string|max:255',

            'required_points' => 'required|integer|min:1',
            'reward_type' => 'required|string|max:255',
            'reward_value' => 'required|string|max:255',
            'valid_days' => 'nullable|integer|min:1',
        ];
    }
}
