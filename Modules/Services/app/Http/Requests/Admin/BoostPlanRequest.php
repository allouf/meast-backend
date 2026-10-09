<?php

namespace Modules\Services\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BoostPlanRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'duration_days' => 'required|integer|min:1',
            /** Placement policy (numeric keys allowed for legacy payloads). Use associative keys like search_rank_multiplier, show_sponsored_badge where needed. */
            'targeting' => 'required|array',
            'is_active' => 'nullable|boolean',
            'variants' => 'nullable|array',
            'variants.*.country_code' => 'nullable|string|size:2',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.currency' => 'required|string|size:3',
            'variants.*.is_active' => 'nullable|boolean',
        ];
    }
}
