<?php

namespace Modules\Services\Http\Resources\User;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;
use Modules\Services\Utilities\ServiceFormatHelper;

class ServiceOfferOrderPhaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $category = $this->relationLoaded('serviceCategory') ? $this->serviceCategory : null;
        $isCursor = (bool) ($this->is_cursor ?? $this->additional['is_cursor'] ?? false);

        $revisionsUsed = $this->relationLoaded('revisions')
            ? $this->revisions->where('status', '!=', ServiceOfferOrderRevisionStatus::Rejected)->count()
            : $this->revisionsUsed();

        return [
            'id' => $this->id,
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
            'payment_status' => $this->paymentStatus(),
            'is_cursor' => $isCursor,
            'started_at' => ServiceFormatHelper::apiDateTime($this->started_at),
            'deadline' => ServiceFormatHelper::apiDateTime($this->deadline),
            'delivered_at' => ServiceFormatHelper::apiDateTime($this->delivered_at),
            'completed_at' => ServiceFormatHelper::apiDateTime($this->completed_at),
            'revisions_allowed' => $this->allowedRevisions(),
            'revisions_used' => $revisionsUsed,
            'revisions_remaining' => max(0, $this->allowedRevisions() - $revisionsUsed),
            'deliveries' => $this->whenLoaded('deliveries', fn () => $this->deliveries->map(fn ($d) => [
                'id' => $d->id,
                'version' => $d->version,
                'message' => $d->message,
                'attachments' => collect($d->attachments ?? [])
                    ->map(fn ($file) => FileManager::getUrl($file))
                    ->values(),
                'submitted_at' => ServiceFormatHelper::apiDateTime($d->submitted_at ?? $d->created_at),
            ])),
        ];
    }
}
