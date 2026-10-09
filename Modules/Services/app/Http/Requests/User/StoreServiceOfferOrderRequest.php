<?php

namespace Modules\Services\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\OrderableType;

class StoreServiceOfferOrderRequest extends FormRequest
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
        $orderableType = OrderableType::tryFrom((string) $this->input('orderable_type'));

        $planIdRules = [
            'nullable',
            'integer',
            'min:1',
        ];

        if ($orderableType === OrderableType::ServiceOffer) {
            $planIdRules[] = Rule::exists('service_offer_plans', 'id')->where(
                fn ($query) => $query->where('service_offer_id', (int) $this->input('orderable_id'))
            );
        } elseif ($orderableType === OrderableType::MilestoneTemplate) {
            $planIdRules[] = Rule::exists('service_offer_plans', 'id')->where(
                fn ($query) => $query->where('service_offer_id', (int) $this->input('service_offer_id'))
            );
        }

        return [
            'orderable_type' => ['required', 'string', Rule::in(OrderableType::values())],
            'orderable_id' => [
                Rule::requiredIf($orderableType === OrderableType::ServiceOffer),
                'nullable',
                'integer',
                'min:1',
                Rule::prohibitedIf($orderableType === OrderableType::MilestoneTemplate),
                Rule::when(
                    $orderableType === OrderableType::ServiceOffer,
                    Rule::exists('service_offers', 'id')
                ),
            ],
            'plan_id' => $planIdRules,
            'service_offer_id' => [
                Rule::requiredIf($orderableType === OrderableType::MilestoneTemplate),
                'nullable',
                'integer',
                'min:1',
                Rule::prohibitedIf($orderableType === OrderableType::ServiceOffer),
                Rule::exists('service_offers', 'id'),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'orderable_type.required' => __('services::messages.order.orderable_type_required'),
            'orderable_type.in' => __('services::messages.order.invalid_orderable_type'),
            'orderable_id.required' => __('services::messages.order.orderable_id_required'),
            'service_offer_id.required' => __('services::messages.order.service_offer_id_required'),
            'plan_id.exists' => __('services::messages.order.invalid_plan'),
        ];
    }
}
