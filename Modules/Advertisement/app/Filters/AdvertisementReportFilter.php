<?php

namespace Modules\Advertisement\Filters;

use Illuminate\Database\Eloquent\Builder;

class AdvertisementReportFilter extends QueryFilter
{
    public function apply(Builder $builder): Builder
    {
        if (isset($this->filters['status'])) {
            $builder->where('status', $this->filters['status']);
        }
        if (isset($this->filters['advertisement_id'])) {
            $builder->where('advertisement_id', $this->filters['advertisement_id']);
        }
        if (isset($this->filters['reason'])) {
            $builder->where('reason', $this->filters['reason']);
        }

        return $builder;
    }
}
