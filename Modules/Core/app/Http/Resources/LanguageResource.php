<?php

namespace Modules\Core\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->header('Accept-Language') ?: 'en';
        // Some headers might have 'en-US,en;q=0.9', we want the first part.
        $locale = explode(',', $locale)[0];
        $locale = explode('-', $locale)[0];

        return [
            'id' => $this->id,
            'name' => $this->getNameForLocale($locale),
            'code' => $this->code,
            'sort_order' => $this->sort_order,
        ];
    }
}
