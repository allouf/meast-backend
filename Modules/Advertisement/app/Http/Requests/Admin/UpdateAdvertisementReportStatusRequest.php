<?php

namespace Modules\Advertisement\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdvertisementReportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['pending', 'reviewed', 'resolved', 'dismissed'])],
            'admin_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be one of: pending, reviewed, resolved, dismissed.',
            'admin_notes.max' => 'The admin notes cannot exceed 1000 characters.',
        ];
    }
}
