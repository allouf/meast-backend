<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Services\Http\Requests\Provider\Concerns\ValidatesAvailableSlotTimeRange;

class UpdateAllServiceOfferAvailableSlotsRequest extends FormRequest
{
    use ValidatesAvailableSlotTimeRange;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->all();
        if (! isset($payload['slots']) && is_array($payload) && $this->isArrayOfSlotObjects($payload)) {
            $this->merge(['slots' => array_values($payload)]);
        }
    }

    private function isArrayOfSlotObjects(array $a): bool
    {
        if ($a === []) {
            return true;
        }
        $first = reset($a);
        return is_array($first) && (isset($first['day']) || isset($first['start_time']) || isset($first['end_time']));
    }

    /**
     * Accepts { "slots": [ { "day", "start_time", "end_time" }, ... ] } or root array [ {...}, ... ].
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'slots' => 'required|array',
            'slots.*.day' => 'required|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateFlatSlotsEndAfterStart($validator, 'slots');
        });
    }

    /** @return array<int, array{day: string, start_time: string, end_time: string}> */
    public function getSlots(): array
    {
        return array_values($this->validated('slots', []));
    }
}
