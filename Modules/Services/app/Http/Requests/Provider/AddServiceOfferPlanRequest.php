<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\DeliveryTimeUnit;

class AddServiceOfferPlanRequest extends FormRequest
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
            'title' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'delivery_time_value' => 'required|integer|gte:1|lte:2147483647',

            'delivery_time_unit' => [
                'required',
                Rule::in(DeliveryTimeUnit::values()),
            ],
            'revision_window' => 'nullable|string|max:100',
            'number_of_revisions' => 'nullable|integer|gte:0|lte:10000',
            'revision_scope_rules' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|gte:0|lte:2147483647',
        ];
    }
}
