<?php

namespace App\Http\Resources\Admin\Settings;

use App\Http\Resources\Admin\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'subject' =>$this->subject,
            'message' =>$this->message,
            'user' =>new UserResource($this->whenLoaded('user')),

        ];
    }
}
