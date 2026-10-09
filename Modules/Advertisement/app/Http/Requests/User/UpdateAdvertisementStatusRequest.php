<?php

namespace Modules\Advertisement\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Advertisement\Enums\AdvertisementStatus;

class UpdateAdvertisementStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_column(AdvertisementStatus::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be one of: ' . implode(', ', array_column(AdvertisementStatus::cases(), 'value')),
        ];
    }
}
