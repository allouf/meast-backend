<?php

namespace Modules\Advertisement\Http\Resources\Admin;

use App\Utilities\FileManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class AdvertisementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->is_free ? 0 : $this->price,
            'is_free' => $this->is_free,
            'is_price_negotiable' => $this->is_price_negotiable,
            'condition' => $this->condition,
            'status' => $this->status,
            'contact_method' => $this->contact_method ?? null,
            'phone' => $this->phone ?? null,
            'show_phone' => $this->show_phone ?? false,
            'views_count' => $this->views_count,
            'published_at' => $this->published_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'renewed_at' => $this->renewed_at?->toDateTimeString(),
            'attributes' => $this->attributes,
            'is_owner' => $this->user_id === Auth::id(),
            'can_renew' => method_exists($this->resource, 'canRenew') ? $this->canRenew() : false,
            'is_favorited' => $this->when(Auth::guard('api')->check(), function () {
                $userId = Auth::guard('api')->id();
                if ($userId === null) return false;
                if ($this->relationLoaded('favoritedBy')) return $this->favoritedBy->contains('id', $userId);
                return $this->favorites()->where('user_id', $userId)->exists();
            }, false),
            'favorites_count' => $this->whenLoaded('favorites', fn () => $this->favorites->count(), $this->favorites()->count()),
            'user' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return ['id' => $this->user->id, 'name' => $this->user->name, 'photo' => $this->user->photo ? FileManager::getUrl($this->user->photo) : null, 'type' => $this->user->type ?? null];
            }),
            'category' => $this->when($this->relationLoaded('category') && $this->category, function () {
                return ['id' => $this->category->id, 'name' => $this->category->name, 'slug' => $this->category->slug, 'icon' => $this->category->icon ? FileManager::getUrl($this->category->icon) : null];
            }),
            'subcategory' => $this->when($this->relationLoaded('subcategory') && $this->subcategory, function () {
                return ['id' => $this->subcategory->id, 'name' => $this->subcategory->name, 'slug' => $this->subcategory->slug];
            }),
            'images' => $this->when($this->images, function () {
                $images = is_array($this->images) ? $this->images : [];
                return array_values(array_filter(array_map(fn ($path) => $path ? FileManager::getUrl($path) : null, $images)));
            }, []),
            'country_code' => $this->country_code ?? null,
            'region' => $this->region ?? null,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
