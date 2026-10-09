<?php

namespace Modules\Store\Filters;

use Illuminate\Database\Eloquent\Builder;

class StoreFilter
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function apply(Builder $builder): Builder
    {
        $filters = $this->filters;

        if (!empty($filters['search'] ?? null)) {
            $searchTerm = $filters['search'];
            $builder->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('emails', 'like', '%' . $searchTerm . '%')
                      ->orWhere('phones', 'like', '%' . $searchTerm . '%');
            });
        }

        if (!empty($filters['name'])) {
            $builder->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['emails'])) {
            $builder->where('emails', 'like', '%' . $filters['email'] . '%');
        }

        if (!empty($filters['phones'])) {
            $builder->where('phones', 'like', '%' . $filters['phone'] . '%');
        }

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_freezone'])) {
            $builder->where('is_freezone', $filters['is_freezone']);
        }

        if (!empty($filters['region'])) {
            $builder->where('region', 'LIKE', '%' . $filters['region'] . '%');
        }

        if (!empty($filters['category_id'])) {
            $builder->whereHas('categories', function ($q) use ($filters) {
                $q->where('id', $filters['category_id']);
            });
        }

        return $builder;
    }
}
