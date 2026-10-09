<?php

namespace Modules\Store\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\Core\Services\LocationContextService;

class ProductLocationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $locationService = app(LocationContextService::class);

        if (!$locationService->isEnabled() || !$locationService->hasLocation()) {
            return;
        }

        $filters = $locationService->getFilters();

        if (empty($filters)) {
            return;
        }

        $builder->whereHas('store', function ($query) use ($filters) {
            if (!empty($filters['country_code'])) {
                $query->where('country_code', $filters['country_code']);
            }
            if (!empty($filters['city'])) {
                $query->where('city', $filters['city']);
            }
            if (!empty($filters['region'])) {
                $query->where('region', 'LIKE', '%' . $filters['region'] . '%');
            }
        });
    }

    public function extend(Builder $builder): void
    {
        $builder->macro('withoutLocation', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('withAnyLocation', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
