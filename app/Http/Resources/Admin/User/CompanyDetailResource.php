<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class CompanyDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->user->id,
            'name'           => $this->user->name ?? null,
            'email'          => $this->user->email ?? null,
            'phone'          => $this->user->phone ?? null,
            'country_code' => $this->user->country_code ?? null,
            'region'       => $this->user->region ?? null,
            'is_active'      => $this->user->is_active ?? true,
            'is_verified'    => !is_null($this->user->verified_at),
            'role'           => $this->user->getRoleNames()->first(),
            'company_name'   => $this->company_name,
            'registration_no' => $this->registration_no,
            'vat_tax_id'     => $this->vat_tax_id,
            'logo'           => $this->logo ? FileManager::getUrl($this->logo) : null,
            'trade_license'  => $this->trade_license ? FileManager::getUrl($this->trade_license) : null,
            'website'        => $this->website,

            'created_at'     => $this->created_at?->toDateTimeString(),
        ];
    }
}
