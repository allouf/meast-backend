<?php

namespace Modules\Store\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class ViolationFileResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'file_url'  => $this->file_path ? FileManager::getUrl($this->file_path) : null,
            'type'      => $this->type,
        ];
    }
}
