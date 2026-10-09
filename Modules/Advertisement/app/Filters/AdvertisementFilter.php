<?php

namespace Modules\Advertisement\Filters;

use Illuminate\Database\Eloquent\Builder;

class AdvertisementFilter extends QueryFilter
{
    protected static array $sortMap = [
        'most_viewed' => [['views_count', 'desc']],
        'views_desc' => [['views_count', 'desc']],
        'least_viewed' => [['views_count', 'asc']],
        'newest_ads' => [['published_at', 'desc'], ['created_at', 'desc']],
        'created_desc' => [['created_at', 'desc']],
        'lowest_price' => [['price', 'asc']],
        'price_asc' => [['price', 'asc']],
        'highest_price' => [['price', 'desc']],
        'price_desc' => [['price', 'desc']],
        'oldest' => [['published_at', 'asc']],
    ];

    public function apply(Builder $builder): Builder
    {
        if (isset($this->filters['category_id'])) {
            $builder->where('advertisement_category_id', $this->filters['category_id']);
        }
        if (isset($this->filters['subcategory_id'])) {
            $builder->where('advertisement_subcategory_id', $this->filters['subcategory_id']);
        }
        if (isset($this->filters['country_code'])) {
            $builder->where('country_code', $this->filters['country_code']);
        }
        if (isset($this->filters['region'])) {
            $builder->where('region', 'LIKE', '%' . $this->filters['region'] . '%');
        }
        if (isset($this->filters['condition'])) {
            $builder->where('condition', $this->filters['condition']);
        }
        if (isset($this->filters['min_price'])) {
            $builder->where('price', '>=', $this->filters['min_price']);
        }
        if (isset($this->filters['max_price'])) {
            $builder->where('price', '<=', $this->filters['max_price']);
        }
        if (isset($this->filters['has_images'])) {
            $builder->whereNotNull('images');
        }
        if (isset($this->filters['is_free'])) {
            $builder->where('is_free', filter_var($this->filters['is_free'], FILTER_VALIDATE_BOOLEAN));
        }
        if (isset($this->filters['is_price_negotiable'])) {
            $builder->where('is_price_negotiable', filter_var($this->filters['is_price_negotiable'], FILTER_VALIDATE_BOOLEAN));
        }
        $this->applyStatus($builder);
        $this->applySort($builder);

        return $builder;
    }

    protected function applyStatus(Builder $builder): void
    {
        if ($this->filled('status')) {
            if ($this->filters['status'] !== 'all') {
                $builder->where('status', $this->filters['status']);
            }
            return;
        }

        if (empty($this->filters['for_admin']) && empty($this->filters['user_id'])) {
            $builder->active();
        }
    }

    protected function applySort(Builder $builder): void
    {
        $sort = $this->value('sort', 'newest_ads');
        $orders = self::$sortMap[$sort] ?? [['published_at', 'desc'], ['created_at', 'desc']];
        foreach ($orders as $order) {
            $builder->orderBy($order[0], $order[1]);
        }
    }
}
