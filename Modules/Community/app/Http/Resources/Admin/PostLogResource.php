<?php

namespace Modules\Community\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'user'   => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'changes'   => $this->changes,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
