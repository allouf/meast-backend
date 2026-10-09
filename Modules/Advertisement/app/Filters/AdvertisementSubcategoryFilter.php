<?php

namespace Modules\Advertisement\Filters;

use Illuminate\Database\Eloquent\Builder;

class AdvertisementSubcategoryFilter extends QueryFilter
{
    public function apply(Builder $builder): Builder
    {
        if (isset($this->filters['active_only'])) {
            $builder->active();
        }
        if (isset($this->filters['category_id'])) {
            $builder->where('advertisement_category_id', $this->filters['category_id']);
        }

        return $builder;
    }
}
