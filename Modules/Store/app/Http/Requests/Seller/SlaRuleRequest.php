<?php

namespace Modules\Store\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SlaRuleRequest extends FormRequest
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
            'store_id'        => 'required|exists:stores,id',
            'issue_type_id'   => 'required|exists:issue_types,id',
            'sla_response_hours' => 'required|integer|min:1',
        ];
    }
}
