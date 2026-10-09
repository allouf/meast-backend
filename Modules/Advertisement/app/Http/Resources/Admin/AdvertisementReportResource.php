<?php

namespace Modules\Advertisement\Http\Resources\Admin;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrls = [];
        if ($this->images && is_array($this->images)) {
            $imageUrls = array_values(array_filter(array_map(fn ($image) => $image ? FileManager::getUrl($image) : null, $this->images)));
        }
        return [
            'id' => $this->id,
            'advertisement_id' => $this->advertisement_id,
            'reason' => $this->reason,
            'description' => $this->description,
            'images' => $imageUrls,
            'status' => $this->status,
            'admin_notes' => $this->admin_notes,
            'advertisement' => $this->whenLoaded('advertisement', function () {
                return [
                    'id' => $this->advertisement->id,
                    'title' => $this->advertisement->title,
                    'slug' => $this->advertisement->slug,
                    'status' => $this->advertisement->status,
                    'user' => $this->advertisement->user ? ['id' => $this->advertisement->user->id, 'name' => $this->advertisement->user->name] : null,
                ];
            }),
            'reporter' => $this->whenLoaded('reporter', function () {
                return ['id' => $this->reporter->id, 'name' => $this->reporter->name, 'email' => $this->reporter->email];
            }),
            'reviewer' => $this->whenLoaded('reviewer', function () {
                return ['id' => $this->reviewer->id, 'name' => $this->reviewer->name];
            }),
            'reviewed_at' => $this->reviewed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
