<?php

namespace App\Http\Resources\User\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'email' => $this->email ?? null,
            'gender' => $this->gender ?? null,
            'birthday' => $this->birthday ?? null,
            'country_code' => $this->country_code ?? null,
            'region' => $this->region ?? null,
            'photo' => $this->photo ? FileManager::getUrl($this->photo) : null,
            'type' => $this->type ?? null,
            'referral_link' => $this->referral_link ?? null,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
