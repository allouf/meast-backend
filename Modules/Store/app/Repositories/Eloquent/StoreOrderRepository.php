<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Store\Models\StoreOrder;
use Modules\Store\Repositories\Interfaces\StoreOrderRepositoryInterface;

class StoreOrderRepository implements StoreOrderRepositoryInterface
{
    use PaginateTrait;

    public function __construct(
        protected StoreOrder $model
    ) {}

    public function findById(int $id): ?StoreOrder
    {
        return $this->model->find($id);
    }

    public function create(array $data): StoreOrder
    {
        return $this->model->create($data);
    }

    public function getStoreOrderWithItems(int $storeOrderId): ?StoreOrder
    {
        return $this->model->with([
            'items.variant.optionValues',
            'items.variant.product.options.values',
            'items.variant.product.gallery',
            'items.variant.product.store',
            'address',
            'store',
            'masterOrder.user'
        ])->find($storeOrderId);
    }

    public function getStoreOrdersByStoreId(int $storeId, array $filters = [])
    {
        $query = $this->model->where('store_id', $storeId)
            ->with([
                'items.variant.optionValues',
                'items.variant.product.options.values',
                'address',
                'masterOrder.user'
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['shipping_method'])) {
            $query->where('shipping_method', $filters['shipping_method']);
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        } elseif (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
        }

        if (!empty($filters['search'])) {
            $query->whereHas('masterOrder.user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }

        $query->orderBy('created_at', 'desc');

        return $this->paginate($query);
    }

    public function getStoreOrdersByMasterOrderId(int $masterOrderId)
    {
        return $this->model->where('master_order_id', $masterOrderId)
            ->with([
                'items.variant.optionValues',
                'items.variant.product.options.values',
                'address',
                'store',
                'masterOrder.user'
            ])
            ->get();
    }

    public function update(int $storeOrderId, array $data): bool
    {
        return $this->model->where('id', $storeOrderId)->update($data);
    }

    public function getOrderStatisticsByStoreId(int $storeId, int $userId): array
    {
        $baseQuery = $this->model->where('store_id', $storeId);

        $totalOrders = (clone $baseQuery)->count();

        $newOrders = (clone $baseQuery)->where('status', 'placed')->count();

        $activeOrders = (clone $baseQuery)->whereIn('status', [
            'placed',
            'seller_confirmed',
            'in_progress',
            'fulfilled'
        ])->count();

        $cancelledOrders = (clone $baseQuery)->whereIn('status', [
            'cancelled_by_seller',
            'cancelled_timeout'
        ])->count();

        return [
            'total_orders' => $totalOrders,
            'active_orders' => $activeOrders,
            'new_orders' => $newOrders,
            'cancelled_orders' => $cancelledOrders,
        ];
    }
}
