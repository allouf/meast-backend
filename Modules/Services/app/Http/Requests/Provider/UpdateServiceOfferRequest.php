<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\DeliveryModel;
use Modules\Services\Enums\DeliveryTimeUnit;
use Modules\Services\Enums\ServiceOfferType;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesAvailableSlotTimeRange;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesServiceOfferPricing;

class UpdateServiceOfferRequest extends FormRequest
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
            'title' => 'sometimes|string|max:255',
            'service_category_id' => 'sometimes|integer|exists:service_categories,id',
            'type' => ['sometimes', 'string', Rule::enum(ServiceOfferType::class)],
            'delivery_model' => ['sometimes', 'string', Rule::enum(DeliveryModel::class)],

            'cover' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:10240',

            'media' => 'nullable|array',
            'media.*.id' => 'nullable|integer|exists:service_offer_media,id',
            'media.*.file' => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:10240',
            'media.*.sort_order' => 'nullable|integer|gte:0|lte:2147483647',

            'cover_media_id' => 'nullable|integer|exists:service_offer_media,id',

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
            'milestone_available' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|gte:0|lte:2147483647',
        ], $this->basePricingFieldRules());
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if ($this->hasFile('cover') && $this->filled('cover_media_id')) {
                $validator->errors()->add(
                    'cover',
                    'You cannot send both cover file and cover_media_id'
                );
            }
            foreach ($this->input('media', []) as $index => $item) {

                if (
                    empty($item['id']) &&
                    !$this->file("media.$index.file")
                ) {
                    $validator->errors()->add(
                        "media.$index",
                        'Each media item must have either id or file'
                    );
                }
            }

            $this->validateNestedAvailableSlotsEndAfterStart($validator);
            $this->validateServiceOfferPricing($validator);
        });
    }
}
