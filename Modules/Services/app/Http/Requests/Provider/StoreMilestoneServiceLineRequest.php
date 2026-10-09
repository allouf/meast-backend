<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreMilestoneServiceLineRequest extends FormRequest
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
            'service_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:50000',
            'service_category_id' => 'required|integer|exists:service_categories,id',
            'duration_days' => 'required|integer|min:1|max:3650',
            'budget' => 'required|numeric|min:0',
            'revision_window_days' => 'nullable|integer|min:0|max:365',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ];
    }
}
