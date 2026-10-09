<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreMemberResource extends JsonResource
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
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email, 
                'phone' => $this->user->phone,
                'photo' => $this->user->photo,
            ] : null,
            'role' => $this->role->value,
            'role_display' => $this->role->getDisplayName(),
            'permissions' => $this->role->getPermissions(),
            'status' => $this->status,
            'invited_at' => $this->created_at,
            'accepted_at' => $this->accepted_at,
            'revoked_at' => $this->revoked_at,
            'invited_by' => $this->inviter ? [
                'id' => $this->inviter->id,
                'name' => $this->inviter->name,
            ] : null,
            'revoked_by' => $this->revoker ? [
                'id' => $this->revoker->id,
                'name' => $this->revoker->name,
            ] : null,
            'store' => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                ];
            }),
        ];
    }
}

