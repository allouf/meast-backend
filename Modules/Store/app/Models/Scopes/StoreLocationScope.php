<?php

namespace Modules\Store\Models\Scopes;

use Modules\Core\Services\LocationContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class StoreLocationScope implements Scope
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

        if (!empty($filters['country_code'])) {
            $builder->where('country_code', $filters['country_code']);
        }

        if (!empty($filters['region'])) {
            $builder->where('region', 'LIKE', '%' . $filters['region'] . '%');
        }
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
