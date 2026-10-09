<?php

namespace Modules\Store\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Store\Enums\StoreOrderStatus;

class UpdateOrderStatusRequest extends FormRequest
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
        $allowedStatuses = array_column(StoreOrderStatus::cases(), 'value');

        return [
            'status' => [
                'required',
                'string',
                'in:' . implode(',', $allowedStatuses),
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status is required.',
            'status.in' => 'The selected status is invalid.',
        ];
    }
}

