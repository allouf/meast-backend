<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\Order;

interface CustomRequestOrderRepositoryInterface
{
    public function create(array $data): Order;

    public function findForPartyOrFail(int $userId, int $orderId): Order;

    public function lockForClientOrFail(int $clientId, int $orderId): Order;

    public function update(Order $order, array $attributes): Order;
}
