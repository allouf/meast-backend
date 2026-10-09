<?php

namespace Modules\Core\Http\Resources\User\Account;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ConsumerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone ?? null,
            'email' => $this->email ?? null,
            'country_code' => $this->country_code ?? null,
            'region' => $this->region ?? null,
            'is_verified' => !is_null($this->verified_at),
            'is_active' => $this->is_active,
            'is_profile_complete' => $this->is_profile_complete,
            'role' => $this->getDisplayRoleName(),
            'photo' => $this->photo ? FileManager::getUrl($this->photo) : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->diffForHumans() : null,
        ];
    }
}
