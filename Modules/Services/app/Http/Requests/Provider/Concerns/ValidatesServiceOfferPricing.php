<?php

namespace Modules\Services\Http\Requests\Provider\Concerns;

use Illuminate\Validation\Validator;
use Modules\Services\Enums\DeliveryTimeUnit;

trait ValidatesServiceOfferPricing
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function basePricingFieldRules(): array
    {
        return [
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'base_currency' => ['nullable', 'string', 'max:10'],
            'base_delivery_time_value' => ['nullable', 'integer', 'gte:1', 'lte:2147483647'],
            'base_delivery_time_unit' => ['nullable', 'string', \Illuminate\Validation\Rule::enum(DeliveryTimeUnit::class)],
            'base_number_of_revisions' => ['nullable', 'integer', 'gte:0', 'lte:10000'],
            'base_revision_window' => ['nullable', 'integer', 'gte:0', 'lte:365'],
        ];
    }

    protected function validateServiceOfferPricing(Validator $validator): void
    {
        $plans = $this->input('plans');

        if ($plans === null) {
            return;
        }

        $hasPlans = is_array($plans) && count($plans) > 0;

        if ($hasPlans) {
            if ($this->filled('base_price') || $this->filled('base_currency')
                || $this->filled('base_delivery_time_value') || $this->filled('base_delivery_time_unit')
                || $this->filled('base_revision_window')) {
                $validator->errors()->add(
                    'base_price',
                    __('services::messages.service_offers.base_price_prohibited_with_plans')
                );
            }

            return;
        }

        if (! $this->filled('base_price')) {
            $validator->errors()->add('base_price', __('services::messages.service_offers.base_price_required_without_plans'));
        }

        if (! $this->filled('base_currency')) {
            $validator->errors()->add('base_currency', __('services::messages.service_offers.base_currency_required_without_plans'));
        }

        $hasValue = $this->filled('base_delivery_time_value');
        $hasUnit = $this->filled('base_delivery_time_unit');

        if ($hasValue xor $hasUnit) {
            $validator->errors()->add(
                'base_delivery_time',
                __('services::messages.service_offers.base_delivery_time_required')
            );
        }
    }
}
