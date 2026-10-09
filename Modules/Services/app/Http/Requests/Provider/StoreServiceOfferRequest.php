<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Enums\DeliveryTimeUnit;
use Modules\Services\Enums\ServiceOfferType;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesAvailableSlotTimeRange;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesServiceOfferPricing;

class StoreServiceOfferRequest extends FormRequest
{
    use ValidatesAvailableSlotTimeRange;
    use ValidatesServiceOfferPricing;

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
            'service_category_id' => 'required|integer|exists:service_categories,id',
            'type' => ['required', 'string', Rule::enum(ServiceOfferType::class)],
            'delivery_model' => ['required', 'string', Rule::enum(DeliveryModel::class)],

            'cover' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240',

            'media' => 'nullable|array',
            'media.*.file' => 'required|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:10240',
            'media.*.sort_order' => 'nullable|integer|gte:0|lte:2147483647',

            'description' => 'nullable|string|max:50000',
            'plans' => 'nullable|array',
            'plans.*.title' => 'nullable|string|max:255',
            'plans.*.price' => 'required|numeric|min:0',
            'plans.*.currency' => 'required|string|max:10',
            'plans.*.delivery_time_value' => 'nullable|integer|gte:1|lte:2147483647',
            'plans.*.delivery_time_unit' => ['nullable', Rule::enum(DeliveryTimeUnit::class)],
            'plans.*.revision_window' => 'nullable|string|max:100',
            'plans.*.number_of_revisions' => 'nullable|integer|gte:0|lte:10000',
            'plans.*.revision_scope_rules' => 'nullable|string|max:2000',
            'faqs' => 'nullable|array',
            'faqs.*.question' => 'required|string|max:1000',
            'faqs.*.answer' => 'required|string|max:5000',
            'available_slots' => 'nullable|array',
            'available_slots.*.day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'available_slots.*.slots' => 'required|array',
            'available_slots.*.slots.*.start_time' => 'required|date_format:H:i',
            'available_slots.*.slots.*.end_time' => 'required|date_format:H:i',
            'is_active' => 'nullable|boolean',
            'milestone_available' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|gte:0|lte:2147483647',
        ], $this->basePricingFieldRules());
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            foreach ($this->plans ?? [] as $index => $plan) {

                $hasValue = isset($plan['delivery_time_value']);
                $hasUnit  = isset($plan['delivery_time_unit']);

                if ($hasValue xor $hasUnit) {
                    $validator->errors()->add(
                        "plans.$index.delivery_time",
                        'delivery_time_value and delivery_time_unit must be provided together'
                    );
                }
            }

            $this->validateNestedAvailableSlotsEndAfterStart($validator);
            $this->validateServiceOfferPricing($validator);
        });
    }
}
