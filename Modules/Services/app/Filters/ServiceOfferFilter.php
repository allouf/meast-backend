<?php

namespace Modules\Services\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ServiceOfferFilter extends QueryFilter
{
    protected static array $sortMap = [
        'newest' => [['created_at', 'desc']],
        'oldest' => [['created_at', 'asc']],
        'lowest_price' => [['subquery_min_plan_price', 'asc']],
        'highest_price' => [['subquery_min_plan_price', 'desc']],
        'lowest_rate' => [['created_at', 'desc']],
        'highest_rate' => [['created_at', 'desc']],
        'sort_order_asc' => [['sort_order', 'asc'], ['created_at', 'desc']],
        'sort_order_desc' => [['sort_order', 'desc'], ['created_at', 'desc']],
        'title_asc' => [['title', 'asc'], ['id', 'asc']],
        'title_desc' => [['title', 'desc'], ['id', 'desc']],
    ];

    /**
     * @param  bool  $applySort When false, callers apply custom ordering (e.g. boosted public search).
     */
    public function apply(Builder $builder, bool $applySort = true): Builder
    {
        if ($this->filled('category_id') || $this->filled('service_category_id')) {
            $categoryId = $this->filters['category_id'] ?? $this->filters['service_category_id'] ?? null;
            if ($categoryId !== null) {
                $builder->where('service_category_id', $categoryId);
            }
        }
        if ($this->filled('delivery_model')) {
            $builder->where('delivery_model', $this->filters['delivery_model']);
        }
        if ($this->filled('service_date_from')) {
            $builder->whereDate('created_at', '>=', $this->filters['service_date_from']);
        }
        if ($this->filled('service_date_to')) {
            $builder->whereDate('created_at', '<=', $this->filters['service_date_to']);
        }
        if ($this->filled('service_type') || $this->filled('type')) {
            $type = $this->filters['service_type'] ?? $this->filters['type'] ?? null;
            if ($type !== null) {
                $builder->where('type', $type);
            }
        }
        if ($this->filled('status')) {
            $builder->where('status', $this->filters['status']);
        }
        if ($this->filled('is_active')) {
            $builder->where('is_active', (bool) $this->filters['is_active']);
        }
        if ($this->filled('search')) {
            $search = $this->filters['search'];
            $builder->where(function (Builder $q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($this->filled('viewer_country_code')) {
            $cc = strtoupper(trim((string) $this->filters['viewer_country_code']));
            if ($cc !== '') {
                $builder->whereHas('serviceProvider', fn (Builder $q) => $q->where('country_code', $cc));
            }
        }
        $minPrice = $this->filled('min_price') && is_numeric($this->filters['min_price']) ? (float) $this->filters['min_price'] : null;
        $maxPrice = $this->filled('max_price') && is_numeric($this->filters['max_price']) ? (float) $this->filters['max_price'] : null;
        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }
        if ($minPrice !== null && $minPrice >= 0) {
            $table = $this->getModelTable($builder);
            $builder->whereRaw($this->effectivePriceSql($table) . ' >= ?', [$minPrice]);
        }
        if ($maxPrice !== null && $maxPrice >= 0) {
            $table = $this->getModelTable($builder);
            $builder->whereRaw($this->effectivePriceSql($table) . ' <= ?', [$maxPrice]);
        }
        if ($this->filled('min_rating') && is_numeric($this->filters['min_rating'])) {
            $minRating = (float) $this->filters['min_rating'];
            if ($minRating >= 0 && $minRating <= 5) {
                $table = $this->getModelTable($builder);
                if (Schema::hasColumn($table, 'rating')) {
                    $builder->whereRaw('COALESCE(' . $table . '.rating, 0) >= ?', [$minRating]);
                }
            }
        }
        if ($applySort) {
            $this->applySort($builder);
        }

        return $builder;
    }

    protected function effectivePriceSql(string $table): string
    {
        return 'COALESCE(
            (SELECT MIN(price) FROM service_offer_plans WHERE service_offer_plans.service_offer_id = ' . $table . '.id),
            ' . $table . '.base_price
        )';
    }

    protected function getModelTable(Builder $builder): string
    {
        return $builder->getModel()->getTable();
    }

    protected function applySort(Builder $builder): void
    {
        $sort = $this->value('sort', 'newest');
        $orders = self::$sortMap[$sort] ?? self::$sortMap['newest'];
        foreach ($orders as $order) {
            $col = $order[0];
            $dir = $order[1];
            if ($col === 'subquery_min_plan_price') {
                $builder->orderByRaw($this->effectivePriceSql($this->getModelTable($builder)) . ' ' . strtoupper($dir));
            } else {
                $builder->orderBy($col, $dir);
            }
        }
    }
}
