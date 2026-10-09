<?php

namespace App\Http\Resources\User\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class FreelancerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->user->id,
            'name'        => $this->user->name ?? null,
            'email'       => $this->user->email ?? null,
            'phone'       => $this->user->phone ?? null,
            'is_active'   => $this->user->is_active ?? true,
            'is_verified' => !is_null($this->user->verified_at),
            'ref_code'    => $this->user->ref_code ?? null,
            'role'        => $this->user->getRoleNames()->first(),
            'country_code' => $this->user->country_code ?? null,
            'region' => $this->user->region ?? null,
            'profession'  => [
                'id' => $this->profession?->id,
                'name' => $this->profession?->name,
            ],
            'national_id' => $this->national_id,
            'portfolio_url' => $this->portfolio_url,
            'documents' => $this->documents->map(fn($doc) => [
                'id'       => $doc->id,
                'file_url' => FileManager::getUrl($doc->file_path),
                'file_type' => $doc->file_type,
            ]),
            'created_at'  => $this->created_at->toDateTimeString(),
        ];
    }
}
