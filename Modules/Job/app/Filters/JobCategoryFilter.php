<?php

namespace Modules\Job\Filters;

use Illuminate\Database\Eloquent\Builder;

class JobCategoryFilter extends QueryFilter
{
    public function apply(Builder $builder): Builder
    {
        if ($this->filled('status')) {
            $this->filters['status'] === 'active'
                ? $builder->active()
                : $builder->where('is_active', false);
        }
        if ($this->filled('search')) {
            $keywords = explode(' ', trim($this->filters['search']));
            $builder->where(function (Builder $q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword !== '') {
                        $q->where(function (Builder $inner) use ($keyword) {
                            $inner->where('name', 'like', '%' . $keyword . '%')
                                ->orWhere('slug', 'like', '%' . $keyword . '%');
                        });
                    }
                }
            });
        }
        $this->applySort($builder);

        return $builder;
    }

    protected function applySort(Builder $builder): void
    {
        $sortBy = $this->value('sort_by');
        if ($sortBy) {
            match ($sortBy) {
                'most_jobs' => $builder->orderBy('jobs_count', 'desc'),
                'least_jobs' => $builder->orderBy('jobs_count', 'asc'),
                'created_latest' => $builder->orderBy('created_at', 'desc'),
                'created_oldest' => $builder->orderBy('created_at', 'asc'),
                default => $builder->orderBy('sort_order', 'asc')->orderBy('id', 'asc'),
            };
        } else {
            $builder->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
        }
    }
}
