<?php
namespace Modules\Store\Http\Resources\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'store_id'           => $this->store_id,
            'user_id'            => $this->user_id,
            'issue_type'          => new IssueTypeResource($this->whenLoaded('issueType')),
            'priority'            => $this->priority,
            'subject'            => $this->subject,
            'status'             => $this->status,
            'sla' => [
                'sla_response_hours'   => $this->sla_response_hours,
                'due_at'           => $this->sla_due_at?->toDateTimeString(),
                'first_response_at' => $this->first_response_at?->toDateTimeString(),
                'is_breached'      => $this->is_sla_breached,
                'sla_delay' => $this->sla_delay_formatted,
            ],
            'order_items' => $this->whenLoaded('orderItems', function () {
                return $this->orderItems->map(fn ($item) => [
                    'id'         => $item->id,
                    'variant_id' => $item->product_variant_id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                    'subtotal'   => $item->subtotal,
                ]);
            }),
            'messages'           => TicketMessageResource::collection($this->whenLoaded('messages')),
            'store'              => $this->whenLoaded('store', fn() => [
                'id' => $this->store->id,
                'name' => $this->store->name,
                'email' => $this->store->emails && is_array($this->store->emails) && !empty($this->store->emails) 
                    ? $this->store->emails[0] 
                    : null,
                'logo'=> $this->store->logo ? FileManager::getUrl($this->store->logo) : null,
            ]),
            'user'               => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'photo' => $this->user->photo ? FileManager::getUrl($this->user->photo) : null,
            ]),
            'return' => $this->when(
                $this->relationLoaded('returnRequest') && $this->returnRequest,
                fn() => [
                    'preferred_solution' => $this->returnRequest->preferred_solution,
                    'reason'             => $this->returnRequest->reason,
                    'description'        => $this->returnRequest->description,
                    'decision'           => $this->returnRequest->decision,
                    'items' => $this->returnRequest->relationLoaded('items')
                        ? $this->returnRequest->items->map(fn($item) => [
                            'order_item_id' => $item->order_item_id,
                            'quantity'      => $item->quantity,
                        ])
                        : [],
                ]
            ),
            'created_at'         => $this->created_at?->toDateTimeString(),
            'updated_at'         => $this->updated_at?->toDateTimeString(),
        ];
    }
}
