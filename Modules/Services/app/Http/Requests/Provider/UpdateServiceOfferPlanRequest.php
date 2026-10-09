<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\DeliveryTimeUnit;

class UpdateServiceOfferPlanRequest extends FormRequest
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
            'title' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|max:10',
            'delivery_time_value' => 'sometimes|integer|gte:1|lte:2147483647',

            'delivery_time_unit' => [
                'sometimes',
                Rule::in(DeliveryTimeUnit::values()),
            ],
            'revision_window' => 'sometimes|nullable|string|max:100',
            'number_of_revisions' => 'sometimes|nullable|integer|gte:0|lte:10000',
            'revision_scope_rules' => 'sometimes|nullable|string|max:2000',
            'sort_order' => 'sometimes|nullable|integer|gte:0|lte:2147483647',
        ];
    }
}
