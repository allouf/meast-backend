<?php

namespace Modules\Store\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class BestSellerResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->final_price ?? (float) $this->price,
            'gallery' => $this->gallery ? collect($this->gallery)->map(fn($file) => [
                'id' => $file->id,
                'file_path' => isset($file->file_path) ? FileManager::getUrl($file->file_path) : null,
                'file_type' => $file->file_type,
            ]) : [],

        ];
    }
}
