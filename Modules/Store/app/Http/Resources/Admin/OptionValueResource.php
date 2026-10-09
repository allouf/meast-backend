<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'option_id' => $this->option_id ?? null,
            'value' => $this->value ?? null,
            'option' => $this->when($this->relationLoaded('option') && $this->option, function () {
                return [
                    'id' => $this->option->id ?? null,
                    'name' => $this->option->name ?? null,
                    'type' => $this->option->type ?? null,
                ];
            }, null),
        ];
    }
}
