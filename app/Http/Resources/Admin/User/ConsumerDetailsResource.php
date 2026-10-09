<?php

namespace App\Http\Resources\Admin\User;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsumerDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'phone' => $this->phone ?? null,
            'email' => $this->email ?? null,
            'country_code' => $this->country_code ?? null,
            'region' => $this->region ?? null,
            'is_verified' => !is_null($this->verified_at),
            'is_active' => $this->is_active,
            'role' => $this->getRoleNames()->first(),
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->diffForHumans() : null,

        ];
    }
}
