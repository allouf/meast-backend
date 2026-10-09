<?php

namespace Modules\Core\Http\Resources\User\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->user->id,
            'name' => $this->user->name ?? null,
            'email' => $this->user->email ?? null,
            'phone' => $this->user->phone ?? null,
            'photo' => $this->user->photo ? FileManager::getUrl($this->user->photo) : null,
            'country_code' => $this->user->country_code ?? null,
            'region' => $this->user->region ?? null,
            'is_active' => $this->user->is_active ?? true,
            'is_profile_complete' => $this->is_profile_complete,
            'is_verified' => !is_null($this->user->verified_at),
            'role' => $this->user->getDisplayRoleName(),
            'company_name' => $this->company_name,
            'registration_no' => $this->registration_no,
            'vat_tax_id' => $this->vat_tax_id,
            'logo' => $this->logo ? FileManager::getUrl($this->logo) : null,
            'trade_license' => $this->trade_license ? FileManager::getUrl($this->trade_license) : null,
            'website' => $this->website,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
