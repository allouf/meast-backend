<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesAvailableSlotTimeRange;

class AddServiceOfferAvailableSlotRequest extends FormRequest
{
    use ValidatesAvailableSlotTimeRange;

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
            'day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'sort_order' => 'nullable|integer|gte:0|lte:2147483647',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateSingleSlotEndAfterStart($validator);
        });
    }
}
