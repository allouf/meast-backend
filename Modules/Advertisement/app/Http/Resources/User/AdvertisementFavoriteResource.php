<?php

namespace Modules\Advertisement\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementFavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'advertisement' => $this->whenLoaded('advertisement', function () {
                return new AdvertisementResource($this->advertisement);
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
