<?php

namespace Modules\Store\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitViolationRequest extends FormRequest
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
            'type' => ['required', Rule::in(['product', 'store', 'post', 'comment'])],
            'type_id' => ['required', 'integer'],
            'reason_id' => [
                'required',
                'integer',
                Rule::exists('violation_reasons', 'id')->where('is_active', true),
            ],

            'description' => ['nullable', 'string'],
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'], // 5MB max
        ];
    }
}
