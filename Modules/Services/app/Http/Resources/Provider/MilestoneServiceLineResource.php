<?php

namespace Modules\Services\Http\Resources\Provider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneServiceLineResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $category = $this->relationLoaded('serviceCategory') ? $this->serviceCategory : null;

        return [
            'id' => $this->id,
            'milestone_id' => $this->milestone_id,
            'service_name' => $this->service_name,
            'description' => $this->description,
            'service_category_id' => $this->service_category_id,
            'category' => $category ? [
                'id' => $category->id,
                'name' => (string) ($category->getTranslation('name', $locale)
                    ?? $category->getTranslation('name', config('app.fallback_locale'))
                    ?? ''),
                'slug' => $category->slug,
            ] : null,
            'duration_days' => $this->duration_days,
            'budget' => (float) $this->budget,
            'revision_window_days' => $this->revision_window_days,
            'sort_order' => $this->sort_order,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
