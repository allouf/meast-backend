<?php

namespace Modules\Community\Filters;

use Filterable\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReelFilter extends Filter
{
    public function apply(Builder $builder, ?array $options = []): Builder
    {
        $filters = request()->only([
            'status',
            'is_featured',
            'user_id',
            'country_code',
            'region',
            'created_from',
            'created_to',
        ]);

        if (isset($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        if (isset($filters['country_code'])) {
            $builder->where('country_code', $filters['country_code']);
        }

        if (isset($filters['region'])) {
            $builder->where('region', 'LIKE', '%' . $filters['region'] . '%');
        }

        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (isset($filters['is_featured'])) {
            $builder->where('is_featured', (bool) $filters['is_featured']);
        }

        if (isset($filters['created_from'])) {
            $builder->whereDate('created_at', '>=', Carbon::parse($filters['created_from']));
        }

        if (isset($filters['created_to'])) {
            $builder->whereDate('created_at', '<=', Carbon::parse($filters['created_to']));
        }

        return $builder;
    }
}
