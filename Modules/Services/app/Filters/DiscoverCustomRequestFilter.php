<?php

namespace Modules\Services\Filters;

use Illuminate\Database\Eloquent\Builder;

class DiscoverCustomRequestFilter extends QueryFilter
{
    private const BUDGET_OPEN_END = '999999999999999999.99';

    public function apply(Builder $builder): Builder
    {
        if ($this->filled('category_id')) {
            $builder->where('category_id', (int) $this->filters['category_id']);
        }

        $this->applyDeadlineFilters($builder);
        $this->applyBudgetFilters($builder);

        return $builder;
    }

    protected function applyDeadlineFilters(Builder $builder): void
    {
        if ($this->value('deadline_preset') === 'next_7_days') {
            $builder->whereDate('deadline_date', '>=', now()->startOfDay())
                ->whereDate('deadline_date', '<=', now()->addDays(7)->startOfDay());
        }

        if ($this->filled('deadline_from')) {
            $builder->whereDate('deadline_date', '>=', $this->filters['deadline_from']);
        }

        if ($this->filled('deadline_to')) {
            $builder->whereDate('deadline_date', '<=', $this->filters['deadline_to']);
        }
    }

    protected function applyBudgetFilters(Builder $builder): void
    {
        if (! $this->filled('budget_min') && ! $this->filled('budget_max')) {
            return;
        }

        $filterMin = $this->filled('budget_min')
            ? $this->filters['budget_min']
            : 0;
        $filterMax = $this->filled('budget_max')
            ? $this->filters['budget_max']
            : null;

        $table = $builder->getModel()->getTable();

        $builder->whereRaw(
            'NOT (COALESCE('. $table.'.budget_max, ?) < ? OR COALESCE('.$table.'.budget_min, 0) > ?)',
            [
                self::BUDGET_OPEN_END,
                $filterMin,
                $filterMax ?? self::BUDGET_OPEN_END,
            ]
        );
    }
}
