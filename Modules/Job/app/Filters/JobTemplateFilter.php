<?php

namespace Modules\Job\Filters;

use Illuminate\Database\Eloquent\Builder;

class JobTemplateFilter extends QueryFilter
{
    public function apply(Builder $builder): Builder
    {
        if ($this->filled('category_id')) {
            $builder->where('category_id', $this->filters['category_id']);
        }
        if (! empty($this->filters['active_only'])) {
            $builder->active();
        }
        if (isset($this->filters['employer_id'])) {
            $builder->where('employer_id', $this->filters['employer_id']);
        }
        if (isset($this->filters['template_type'])) {
            if ($this->filters['template_type'] === 'system') {
                $builder->where('is_system_template', true);
            }
            if ($this->filters['template_type'] === 'custom') {
                $builder->where('is_system_template', false);
            }
        }
        if (isset($this->filters['status'])) {
            if ($this->filters['status'] === 'active') {
                $builder->active();
            }
            if ($this->filters['status'] === 'inactive') {
                $builder->where('is_active', false);
            }
        }
        if ($this->filled('search')) {
            $builder->where('title', 'like', '%' . $this->filters['search'] . '%');
        }
        $this->applySort($builder);

        return $builder;
    }

    protected function applySort(Builder $builder): void
    {
        $sortBy = $this->value('sort_by');
        if (! $sortBy) {
            return;
        }
        match ($sortBy) {
            'most_jobs' => $builder->orderByDesc('jobs_count'),
            'least_jobs' => $builder->orderBy('jobs_count'),
            'created_latest' => $builder->orderByDesc('created_at'),
            'created_oldest' => $builder->orderBy('created_at'),
            default => $builder->orderByDesc('updated_at'),
        };
    }
}
