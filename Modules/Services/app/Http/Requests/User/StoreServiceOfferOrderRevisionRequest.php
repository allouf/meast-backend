<?php

namespace Modules\Services\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\ServiceOfferOrderRevisionRequestType;

class StoreServiceOfferOrderRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_version' => ['required', 'integer', 'min:1'],
            'notes' => ['required', 'string'],
            'request_type' => ['required', 'string', Rule::in(ServiceOfferOrderRevisionRequestType::values())],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => [
                'file',
                'max:5120',
                'mimes:jpg,jpeg,png,pdf',
            ],
        ];
    }
}
