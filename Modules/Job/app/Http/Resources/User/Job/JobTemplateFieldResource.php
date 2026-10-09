<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobTemplateFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'type' => $this->type,
            'validation_rules' => $this->validation_rules,
            'options' => $this->options,
            'sort_order' => $this->sort_order,
            'is_required' => $this->is_required,
        ];
    }
}
