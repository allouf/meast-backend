<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelancerListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id'        => $this->id,
            'name'      => $user->name ?? null,
            'email'     => $user->email ?? null,
            'phone'     => $user->phone ?? null,
            'is_active' => $user->is_active ?? true,
            'role'      => $user?->getRoleNames()->first(),
            'profession' => $this->profession?->name,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
