<?php

namespace App\Http\Resources\User\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class CompanyProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $gallery = $this->gallery ?? [];
        $galleryUrls = array_map(fn ($path) => FileManager::getUrl($path), $gallery);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $this->company_name,
            'registration_no' => $this->registration_no,
            'vat_tax_id' => $this->vat_tax_id,
            'logo' => $this->logo ? FileManager::getUrl($this->logo) : null,
            'cover' => $this->cover ? FileManager::getUrl($this->cover) : null,
            'trade_license' => $this->trade_license ? FileManager::getUrl($this->trade_license) : null,
            'website' => $this->website,
            'description' => $this->description,
            'gallery' => $galleryUrls,
            'team_members' => CompanyTeamMemberResource::collection($this->whenLoaded('teamMembers')),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
