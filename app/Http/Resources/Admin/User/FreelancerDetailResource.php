<?php

namespace App\Http\Resources\Admin\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class FreelancerDetailResource extends JsonResource
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
            'id'          => $this->id,
            'name'        => $user->name ?? null,
            'email'       => $user->email ?? null,
            'phone'       => $user->phone ?? null,
            'is_active'   => $user->is_active ?? true,
            'is_verified' => !is_null($user->verified_at),
            'ref_code'    => $user->ref_code ?? null,
            'role'        => $user?->getRoleNames()->first(),
            'country_code' => $user->country_code ?? null,
            'region'       => $user->region ?? null,
            'profession'  => [
                'id'   => $this->profession?->id,
                'name' => $this->profession?->name,
            ],
            'national_id'  => $this->national_id,
            'portfolio_url' => $this->portfolio_url,
            'documents'    => $this->documents->map(fn($doc) => [
                'id'        => $doc->id,
                'file_url'  => FileManager::getUrl($doc->file_path),
                'file_type' => $doc->file_type,
            ]),
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];
    }
}
