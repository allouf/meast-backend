<?php

namespace Modules\Job\Filters;

use Illuminate\Database\Eloquent\Builder;

class JobFilter extends QueryFilter
{
    protected static array $sortMap = [
        'newest' => [['created_at', 'desc']],
        'oldest' => [['created_at', 'asc']],
        'deadline_soon' => [['application_deadline', 'asc'], ['created_at', 'desc']],
        'deadline_latest' => [['application_deadline', 'desc'], ['created_at', 'desc']],
        'salary_high' => [['salary_max', 'desc'], ['created_at', 'desc']],
        'salary_low' => [['salary_min', 'asc'], ['created_at', 'desc']],
    ];

    public function apply(Builder $builder): Builder
    {
        if (isset($this->filters['employer_id'])) {
            $builder->where('employer_id', $this->filters['employer_id']);
        }
        if (isset($this->filters['category_id'])) {
            $builder->where('category_id', $this->filters['category_id']);
        }
        if (isset($this->filters['status'])) {
            $builder->where('status', $this->filters['status']);
        }
        if (isset($this->filters['country_code'])) {
            $builder->where('country_code', $this->filters['country_code']);
        }
        if ($this->filled('employment_type')) {
            $builder->where('employment_type', $this->filters['employment_type']);
        }
        if ($this->filled('experience_level')) {
            $builder->where('experience_level', $this->filters['experience_level']);
        }
        if ($this->filled('work_mode')) {
            $builder->where('work_mode', $this->filters['work_mode']);
        }
        if ($this->filled('application_deadline_from')) {
            $builder->whereDate('application_deadline', '>=', $this->filters['application_deadline_from']);
        }
        if ($this->filled('application_deadline_to')) {
            $builder->whereDate('application_deadline', '<=', $this->filters['application_deadline_to']);
        }

        if ($this->filled('company_name')) {
            $keywords = explode(' ', trim($this->filters['company_name']));

            $builder->whereHas('employer.company', function (Builder $q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword !== '') {
                        $q->where('company_name', 'like', '%' . $keyword . '%');
                    }
                }
            });
        }
        $this->applySalaryFilters($builder);
        if ($this->filled('search')) {
            $search = $this->filters['search'];
            $keywords = explode(' ', $search);
            $builder->where(function (Builder $q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if ($keyword !== '') {
                        $q->where(function (Builder $inner) use ($keyword) {
                            $inner->where('title', 'like', '%' . $keyword . '%')
                                ->orWhere('description', 'like', '%' . $keyword . '%');
                        });
                    }
                }
            });
        }
        $this->applySort($builder);

        return $builder;
    }

    protected function applySalaryFilters(Builder $builder): void
    {
        $min = $this->filled('min_salary') ? (float) $this->filters['min_salary'] : null;
        $max = $this->filled('max_salary') ? (float) $this->filters['max_salary'] : null;

        if ($min === null && $max === null) {
            return;
        }

        if ($min !== null && $max !== null && $min > $max) {
            [$min, $max] = [$max, $min];
        }

        $builder->where(function (Builder $q) use ($min, $max) {
            // Rule: At least one end of the job's salary range must fall within the user's filter range.
            // Or the job has no salary info at all.
            $q->where(function (Builder $q2) use ($min, $max) {
                if ($min !== null && $max !== null) {
                    $q2->whereBetween('salary_min', [$min, $max])
                        ->orWhereBetween('salary_max', [$min, $max]);
                } elseif ($min !== null) {
                    $q2->where('salary_min', '>=', $min)
                        ->orWhere('salary_max', '>=', $min);
                } else {
                    $q2->where('salary_min', '<=', $max)
                        ->orWhere('salary_max', '<=', $max);
                }
            })->orWhere(function (Builder $q2) {
                $q2->whereNull('salary_min')->whereNull('salary_max');
            });
        });
    }

    protected function applySort(Builder $builder): void
    {
        $sort = $this->value('sort', 'newest');
        $orders = self::$sortMap[$sort] ?? self::$sortMap['newest'];
        foreach ($orders as $order) {
            $builder->orderBy($order[0], $order[1]);
        }
    }
}
