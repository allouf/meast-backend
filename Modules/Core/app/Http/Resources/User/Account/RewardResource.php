<?php

namespace Modules\Core\Http\Resources\User\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        return [
            'id' => $this->id,
            'redeemed' => (bool) $this->redeemed,
            'milestone' => [
                'name' => $this->milestone?->getTranslation('name', $locale),
                'type' => $this->milestone?->reward_type,
                'value' => $this->milestone?->reward_value,
                'valid_days' => $this->milestone?->valid_days,
                'required_points' => $this->milestone?->required_points,
            ],
        ];
    }
}
