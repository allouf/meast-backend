<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

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
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,
            'role' => $this->role->value,
            'role_display' => $this->role->getDisplayName(),
            'permissions' => $this->role->getPermissions(),
            'status' => $this->status,
            'invitation_token' => $this->invitation_token,
            'invited_by' => $this->invited_by,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'revoked_by' => $this->revoked_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'photo' => $this->user->photo ? FileManager::getUrl($this->user->photo) : null,
                ];
            }),
            'store' => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                    'slug' => $this->store->slug,
                    'logo' => $this->store->logo ? FileManager::getUrl($this->store->logo) : null,
                ];
            }),
            'inviter' => $this->whenLoaded('inviter', function () {
                return [
                    'id' => $this->inviter->id,
                    'name' => $this->inviter->name,
                    'email' => $this->inviter->email,
                ];
            }),
            'revoker' => $this->whenLoaded('revoker', function () {
                return [
                    'id' => $this->revoker->id,
                    'name' => $this->revoker->name,
                    'email' => $this->revoker->email,
                ];
            }),
        ];
    }
}

