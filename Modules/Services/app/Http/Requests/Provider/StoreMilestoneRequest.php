<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesMilestoneServiceLines;

class StoreMilestoneRequest extends FormRequest
{
    use ValidatesMilestoneServiceLines;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:50000',
            'delivery_model' => ['required', 'string', Rule::enum(DeliveryModel::class)],
            'request_modification_days' => 'required|integer|min:0|max:365',
            'activation_status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:10000',
            'service_offer_order_id' => ['required', 'integer', 'min:1', 'exists:service_offer_orders,id'],
        ], $this->milestoneServiceLineRules(required: true));
    }
}
