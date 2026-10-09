<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Store\Models\MasterOrder;
use Modules\Store\Repositories\Interfaces\MasterOrderRepositoryInterface;

class MasterOrderRepository implements MasterOrderRepositoryInterface
{

    use PaginateTrait;

    public function __construct(
        protected MasterOrder $model
    ) {}

    public function findByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function findById(int $id): ?MasterOrder
    {
        return $this->model->find($id);
    }

    public function create(array $data): MasterOrder
    {
        return $this->model->create($data);
    }

    public function getAllMasterOrders(array $filters = [])
    {
        $query = $this->model->with([
            'user',
            'storeOrders.store',
            'storeOrders.address',
            'storeOrders.items.variant.product',
            'storeOrders.items.variant.optionValues',
            'storeOrders.items.variant.product.options.values',
            'storeOrders.items.variant.product.gallery',
            'storeOrders.items.variant.product.store',
        ])
        ->withCount('storeOrders');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        } else {
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
        }

        // فلترة حسب المستخدم (بحث)
        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('email', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
            });
        }


        $query->latest();

        return $this->paginate($query);
    }


    public function getMasterOrderWithStoreOrders(int $masterOrderId): ?MasterOrder
    {
        return $this->model->with([
            'storeOrders.items.variant.optionValues',
            'storeOrders.items.variant.product.options.values',
            'storeOrders.items.variant.product.gallery',
            'storeOrders.items.variant.product.store',
            'storeOrders.address',
            'storeOrders.store',
            'user'
        ])
        ->withCount('storeOrders')
        ->withCount(['storeOrders as total_items_count' => function ($q) {
            $q->join('order_items', 'store_orders.id', '=', 'order_items.store_order_id');
        }])
        ->find($masterOrderId);
    }

    public function getMasterOrdersByUserId(int $userId)
    {
        return $this->model->where('user_id', $userId)
            ->with([
                'storeOrders.items.variant.optionValues',
                'storeOrders.items.variant.product.options.values',
                'storeOrders.items.variant.product.gallery',
                'storeOrders.items.variant.product.store',
                'storeOrders.address',
                'storeOrders.store',
                'storeOrders.masterOrder.user',
                'user'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function update(int $masterOrderId, array $data): bool
    {
        return $this->model->where('id', $masterOrderId)->update($data);
    }
}
