<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesMilestoneServiceLines;

class UpdateMilestoneRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:50000',
            'delivery_model' => ['sometimes', 'string', Rule::enum(DeliveryModel::class)],
            'request_modification_days' => 'sometimes|integer|min:0|max:365',
            'activation_status' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ], $this->milestoneServiceLineRules(required: false));
    }
}
