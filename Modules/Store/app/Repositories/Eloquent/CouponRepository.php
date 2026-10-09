<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Store\Models\Coupon;
use Modules\Store\Repositories\Interfaces\CouponRepositoryInterface;

class CouponRepository implements CouponRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Coupon $model)
    {
        $this->model = $model;
    }

    public function index(array $filters = [])
    {
        $query = $this->model
            ->with(['store', 'categories', 'products'])
            ->withCount('usages')
            ->latest();

        // Filter by store
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        // Search by code
        if (!empty($filters['code'])) {
            $query->where('code', 'LIKE', "%{$filters['code']}%");
        }

        // Discount type
        if (!empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        // Scope
        if (!empty($filters['scope'])) {
            $query->where('scope', $filters['scope']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'valid') {
                $query->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
            }

            if ($filters['status'] === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        // Usage filter
        if (isset($filters['has_usage'])) {
            if ($filters['has_usage']) {
                $query->has('usages');
            } else {
                $query->doesntHave('usages');
            }
        }

        // Date filters
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }

        if (!empty($filters['expires_from'])) {
            $query->whereDate('expires_at', '>=', $filters['expires_from']);
        }

        if (!empty($filters['expires_to'])) {
            $query->whereDate('expires_at', '<=', $filters['expires_to']);
        }

        // Sorting
        if (!empty($filters['sort'])) {
            match ($filters['sort']) {
                'most_used' => $query->orderBy('usages_count', 'desc'),
                'expires_soon' => $query->orderBy('expires_at', 'asc'),
                default => $query->latest(),
            };
        }

        return $this->paginate($query);
    }


    public function findById($id)
    {
        return $this->model
            ->with(['categories', 'products'])
            ->findOrFail($id);
    }


    public function getByStoreId($storeId, ?string $search = null, ?string $status = null)
    {
        $query = $this->model
            ->where('store_id', $storeId)
            ->with(['categories', 'products'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('discount_type', 'LIKE', "%{$search}%")
                    ->orWhereHas('products', function ($p) use ($search) {
                        $p->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('categories', function ($c) use ($search) {
                        $c->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($status == 'valid') {
            $query->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
        } elseif ($status == 'expired') {
            $query->where('expires_at', '<=', now());
        }

        return $this->paginate($query);
    }



    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, Coupon $coupon)
    {
        $coupon->update($data);
        return $coupon;
    }

    public function delete(Coupon $coupon)
    {
        return $coupon->delete();
    }
}
