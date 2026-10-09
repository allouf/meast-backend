<?php

namespace Modules\Services\Http\Requests\Provider\Concerns;

trait ValidatesMilestoneServiceLines
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    protected function milestoneServiceLineRules(bool $required = true): array
    {
        $servicesRule = $required ? 'required' : 'sometimes';

        return [
            'services' => "{$servicesRule}|array|min:1",
            'services.*.service_name' => 'required|string|max:255',
            'services.*.description' => 'nullable|string|max:50000',
            'services.*.service_category_id' => 'required|integer|exists:service_categories,id',
            'services.*.duration_days' => 'required|integer|min:1|max:3650',
            'services.*.budget' => 'required|numeric|min:0',
            'services.*.revision_window_days' => 'nullable|integer|min:0|max:365',
            'services.*.sort_order' => 'nullable|integer|min:0|max:10000',
        ];
    }
}
