<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class OpenDisputeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason_id' => [
                'required',
                'integer',
                'exists:dispute_reasons,id',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_id.required' => 'Dispute reason is required.',
            'reason_id.exists' => 'Invalid dispute reason.',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
