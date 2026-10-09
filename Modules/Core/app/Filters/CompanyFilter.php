<?php

namespace Modules\Core\Filters;

use Illuminate\Database\Eloquent\Builder;

class CompanyFilter extends QueryFilter
{
    public function apply(Builder $builder): Builder
    {
        if ($this->filled('search')) {
            $term = $this->filters['search'];
            $builder->where(function (Builder $q) use ($term) {
                $q->where('company_name', 'like', '%' . $term . '%')
                    ->orWhereHas('jobProfile', function (Builder $q2) use ($term) {
                        $q2->where('bio', 'like', '%' . $term . '%');
                    });
            });
        }

        // فلترة حسب الفئة
        if ($this->filled('category_id')) {
            $builder->where('category_id', $this->filters['category_id']);
        }

        // فلترة حسب الدولة
        if ($this->filled('country_code')) {
            $builder->whereHas('user', function (Builder $q) {
                $q->where('country_code', $this->filters['country_code']);
            });
        }

        // فلترة حسب الحالة
        if ($this->filled('status')) {
            $status = $this->filters['status'];
            $builder->where('status', $status);
        }

        $this->applySort($builder);

        return $builder;
    }

    protected function applySort(Builder $builder): void
    {
        $sortBy = $this->value('sort_by');

        if ($sortBy) {
            match ($sortBy) {
                'name_asc' => $builder->orderBy('company_name', 'asc'),
                'name_desc' => $builder->orderBy('company_name', 'desc'),
                'created_latest' => $builder->orderBy('created_at', 'desc'),
                'created_oldest' => $builder->orderBy('created_at', 'asc'),
                default => $builder->orderBy('id', 'desc'),
            };
        } else {
            $builder->orderBy('id', 'desc');
        }
    }
}
