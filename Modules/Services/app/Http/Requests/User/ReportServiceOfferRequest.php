<?php

namespace Modules\Services\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Services\Enums\ServiceOfferReportReason;

class ReportServiceOfferRequest extends FormRequest
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
            'reason' => 'required|string|in:' . implode(',', ServiceOfferReportReason::values()),
            'description' => 'nullable|string|max:500',
        ];
    }
}
