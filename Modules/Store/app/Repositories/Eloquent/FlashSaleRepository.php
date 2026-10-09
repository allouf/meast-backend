<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Illuminate\Support\Facades\DB;
use Modules\Store\Models\FlashSale;
use Modules\Store\Repositories\Interfaces\FlashSaleRepositoryInterface;

class FlashSaleRepository implements FlashSaleRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(FlashSale $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = [])
    {
        $query = $this->model
            ->with([
                'store',
                'variants.product'
            ])
            ->withCount([
                'variants',
                'variants as product_count' => function ($query) {
                    $query->select(DB::raw('COUNT(DISTINCT product_id)'));
                }
            ])
            ->latest();

        // Filter by store
        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date status filter
        if (!empty($filters['date_status'])) {
            $now = now();

            match ($filters['date_status']) {
                'upcoming' => $query->where('start_date', '>', $now),
                'running'  => $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now),
                'ended'    => $query->where('end_date', '<', $now),
            };
        }

        // Discount type
        if (!empty($filters['discount_type'])) {
            $query->where('discount_type', $filters['discount_type']);
        }

        // Date filters
        if (!empty($filters['start_from'])) {
            $query->whereDate('start_date', '>=', $filters['start_from']);
        }

        if (!empty($filters['start_to'])) {
            $query->whereDate('start_date', '<=', $filters['start_to']);
        }

        if (!empty($filters['end_from'])) {
            $query->whereDate('end_date', '>=', $filters['end_from']);
        }

        if (!empty($filters['end_to'])) {
            $query->whereDate('end_date', '<=', $filters['end_to']);
        }

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('store', function ($s) use ($search) {
                        $s->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Stock filter
        if (isset($filters['has_stock'])) {
            if ($filters['has_stock']) {
                $query->whereHas('variants', fn($q) => $q->where('stock', '>', 0));
            } else {
                $query->whereDoesntHave('variants', fn($q) => $q->where('stock', '>', 0));
            }
        }

        // Sorting
        if (!empty($filters['sort'])) {
            match ($filters['sort']) {
                'start_soon'  => $query->orderBy('start_date', 'asc'),
                'ending_soon' => $query->orderBy('end_date', 'asc'),
                default       => $query->latest(),
            };
        }

        return $this->paginate($query);
    }

    public function findById($id)
    {
        return $this->model->with([
            'store',
            'variants.product.options.values',
            'variants.optionValues',
        ])->findOrFail($id);
    }
    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, FlashSale $flashSale)
    {
        $flashSale->update($data);
        return $flashSale;
    }

    public function delete(FlashSale $flashSale)
    {
        return $flashSale->delete();
    }

    public function getByStoreId(
        int $storeId,
        $paginate = true,
        ?string $search = null,
        array $filters = []
    ) {
        $query = $this->model
            ->where('store_id', $storeId)
            ->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0)
                    ->whereHas('product', function ($productQuery) {
                        $productQuery->where('status', 'active');
                    });
            })
            ->with(['variants' => function ($q) {
                $q->where('stock', '>', 0)
                    ->whereHas('product', function ($productQuery) {
                        $productQuery->where('status', 'active');
                    })
                    ->with([
                        'product.options.values',
                        'optionValues'
                    ]);
            }, 'store'])
            ->withCount([
                'variants as product_count' => function ($query) {
                    $query->select(DB::raw('COUNT(DISTINCT product_id)'));
                }
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('variants.product', function ($p) use ($search) {
                        $p->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('end_date', '<=', $filters['end_date']);
        }


        if (!empty($filters['discount_type'])) {
            dd($filters['discount_type']);

            $query->where('discount_type', $filters['discount_type']);
        }

        // $results = $query->get();
        // dd($results);

        if ($paginate) {
            return $this->paginate($query);
        }

        return [
            'data' => $query->get(),
            'meta' => null
        ];
    }

    public function hasAvailableStock(FlashSale $flashSale): bool
    {
        return $flashSale->variants()
            ->where('stock', '>', 0)
            ->whereHas('product', function ($q) {
                $q->where('status', 'active');
            })
            ->exists();
    }

    public function loadVariantsWithStock(FlashSale $flashSale): FlashSale
    {
        return $flashSale->load(['variants' => function ($q) {
            $q->where('stock', '>', 0)
                ->whereHas('product', function ($productQuery) {
                    $productQuery->where('status', 'active');
                })
                ->with([
                    'product.options.values',
                    'optionValues'
                ]);
        }]);
    }
}
