<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscoverCustomRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('budget_max') && ! $this->filled('budget_min')) {
            $this->merge(['budget_min' => 0]);
        }
    }

    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:custom_request_categories,id'],
            'deadline_from' => ['nullable', 'date'],
            'deadline_to' => ['nullable', 'date', 'after_or_equal:deadline_from'],
            'deadline_preset' => ['nullable', 'string', Rule::in(['next_7_days'])],
            'budget_min' => ['nullable', 'numeric', 'min:0'],
            'budget_max' => ['nullable', 'numeric', 'min:0', 'gte:budget_min'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
