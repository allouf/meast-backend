<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class CompanyListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof \Modules\Core\Models\User) {
            return [
                'id'          => $this->id,
                'name'        => $this->name ?? null,
                'email'       => $this->email ?? null,
                'phone'       => $this->phone ?? null,
                'is_active'   => $this->is_active ?? true,
                'is_verified' => !is_null($this->verified_at),
                'company_name' => $this->company?->company_name ?? null,
                'logo'        => $this->company?->logo ? FileManager::getUrl($this->company->logo) : null,
                'registration_no' => $this->company?->registration_no,
                'vat_tax_id'     => $this->company?->vat_tax_id,
                'trade_license'  => $this->company?->trade_license ? FileManager::getUrl($this->trade_license) : null,
                'website'        => $this->company?->website,
                'created_at'  => $this->created_at?->toDateTimeString(),
            ];
        } else {
            return [
                'id'          => $this->user?->id,
                'name'        => $this->user?->name ?? null,
                'email'       => $this->user?->email ?? null,
                'phone'       => $this->user?->phone ?? null,
                'company_name' => $this->company_name,
                'logo'        => $this->logo ? FileManager::getUrl($this->logo) : null,
                'registration_no' => $this->registration_no,
                'vat_tax_id'     => $this->vat_tax_id,
                'trade_license'  => $this->trade_license ? FileManager::getUrl($this->trade_license) : null,
                'website'        => $this->website,
                'is_active'   => $this->user?->is_active ?? true,
                'is_verified' => !is_null($this->user?->verified_at),
                'created_at'  => $this->created_at?->toDateTimeString(),
            ];
        }
    }
}
