<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\Order;
use Modules\Services\Repositories\Interfaces\CustomRequestOrderRepositoryInterface;

class CustomRequestOrderRepository implements CustomRequestOrderRepositoryInterface
{
    public function __construct(
        protected Order $model
    ) {}

    public function create(array $data): Order
    {
        $order = $this->model->newQuery()->create($data);

        return $order->load(['quote', 'client', 'provider.user:id,name,photo']);
    }

    public function findForPartyOrFail(int $userId, int $orderId): Order
    {
        return $this->model->newQuery()
            ->whereKey($orderId)
            ->where(function ($q) use ($userId) {
                $q->where('client_id', $userId)
                    ->orWhereHas('provider', fn ($pq) => $pq->where('user_id', $userId));
            })
            ->with([
                'quote.customRequest.category',
                'client:id,name,photo',
                'provider.user:id,name,photo',
            ])
            ->firstOrFail();
    }

    public function lockForClientOrFail(int $clientId, int $orderId): Order
    {
        return $this->model->newQuery()
            ->whereKey($orderId)
            ->where('client_id', $clientId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function update(Order $order, array $attributes): Order
    {
        $order->update($attributes);

        return $order->fresh(['quote.customRequest', 'client:id,name,photo', 'provider.user:id,name,photo']);
    }
}
