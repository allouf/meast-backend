<?php

namespace Modules\Advertisement\Filters;

use Illuminate\Database\Eloquent\Builder;

class AdvertisementCategoryFilter extends QueryFilter
{
    public function apply(Builder $builder): Builder
    {
        if (isset($this->filters['active_only'])) {
            $builder->active();
        }

        return $builder;
    }
}
