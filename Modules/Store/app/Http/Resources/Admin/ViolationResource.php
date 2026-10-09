<?php

namespace Modules\Store\Http\Resources\Admin;

use Modules\Store\Enums\ViolationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,

            'user' => $this->whenLoaded('user', function () {
                return [
                    'id'   => $this->user->id,
                    'name' => $this->user->name ?? null,
                    'email'    => $this->user->email ?? null,
                    'phone'    => $this->user->phone ?? null,
                    'is_active' => $this->user->is_active,
                ];
            }),

            'admin' => $this->whenLoaded('admin', function () {
                return $this->admin ? [
                    'id'   => $this->admin->id,
                    'name' => $this->admin->name ?? null,
                ] : null;
            }),

            'reason' => $this->whenLoaded('reason', function () {
                return [
                    'id'    => $this->reason->id,
                    'title' => $this->reason->title,
                ];
            }),

            'description' => $this->description,
            'severity'    => $this->severity,
            'status'      => $this->status,

            'violatable' => $this->whenLoaded('violatable', function () {
                return $this->formatViolatable();
            }),

            'files' => ViolationFileResource::collection(
                $this->whenLoaded('files')
            ),

            'created_at' => $this->created_at?->toDateTimeString(),

            'actions' => [
                'can_review' => $this->status === ViolationStatus::PENDING->value,
                'can_take_action' => $this->status === ViolationStatus::REVIEWED->value,
                'can_ignore' => in_array($this->status, [
                    ViolationStatus::PENDING->value,
                    ViolationStatus::REVIEWED->value
                ]),
            ],

        ];
    }

    protected function formatViolatable()
    {
        if (!$this->violatable) {
            return null;
        }

        return [
            'id'   => $this->violatable->id,
            'type' => class_basename($this->violatable_type),
            'data' => $this->extractViolatableData(),
        ];
    }

    protected function extractViolatableData()
    {
        $model = $this->violatable;

        return match (class_basename($this->violatable_type)) {
            'Store' => [
                'name' => $model->name ?? null,
                'slug' => $model->slug ?? null,
            ],
            'Product' => [
                'name'  => $model->name ?? null,
                'price' => $model->price ?? null,
            ],
            'Post' => [
                'title' => $model->title ?? null,
            ],
            'Comment' => [
                'content' => $model->content ?? null,
            ],
            default => [],
        };
    }
}
