<?php

namespace Modules\Job\Filters;

use Illuminate\Database\Eloquent\Builder;

class JobApplicationFilter extends QueryFilter
{
    public const SORT_NEWEST = 'newest';
    public const SORT_LATEST = 'latest';
    public const SORT_OLDEST = 'oldest';
    public const SORT_HIGHEST_SALARY = 'highest_salary';
    public const SORT_LOWEST_SALARY = 'lowest_salary';
    public const SORT_RATING_HIGH = 'rating_high';
    public const SORT_RATING_LOW = 'rating_low';
    public const SORT_NAME_ASC = 'name_asc';
    public const SORT_NAME_DESC = 'name_desc';

    public function apply(Builder $builder): Builder
    {
        if ($this->filled('status')) {
            $builder->where('status', $this->filters['status']);
        }
        if ($this->filled('job_id')) {
            $builder->where('job_id', $this->filters['job_id']);
        }

        $this->applySearch($builder);
        $this->applySort($builder);

        return $builder;
    }

    protected function applySearch(Builder $builder): void
    {
        if (!$this->filled('search')) {
            return;
        }

        $keywords = explode(' ', trim($this->filters['search']));
        $builder->whereHas('job', function (Builder $q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if ($keyword !== '') {
                    $term = '%' . $keyword . '%';
                    $q->where(function (Builder $inner) use ($term) {
                        $inner->where('title', 'like', $term)
                            ->orWhere('description', 'like', $term)
                            ->orWhereHas('employer', function (Builder $q2) use ($term) {
                                $q2->where('name', 'like', $term);
                            });
                    });
                }
            }
        });
    }

    protected function applySort(Builder $builder): void
    {
        $sort = $this->value('sort', self::SORT_NEWEST);
        $table = $builder->getModel()->getTable();

        switch ($sort) {
            case self::SORT_OLDEST:
                $builder->orderBy("{$table}.created_at", 'asc');
                break;
            case self::SORT_HIGHEST_SALARY:
                $builder->join('job_postings', 'job_applications.job_id', '=', 'job_postings.id')
                    ->orderByRaw('COALESCE(job_postings.salary_max, job_postings.salary_min, 0) DESC')
                    ->select('job_applications.*');
                break;
            case self::SORT_LOWEST_SALARY:
                $builder->join('job_postings', 'job_applications.job_id', '=', 'job_postings.id')
                    ->orderByRaw('COALESCE(job_postings.salary_min, job_postings.salary_max, 999999999) ASC')
                    ->select('job_applications.*');
                break;
            case self::SORT_RATING_HIGH:
                $builder->orderBy("{$table}.rating", 'desc');
                break;
            case self::SORT_RATING_LOW:
                $builder->orderBy("{$table}.rating", 'asc');
                break;
            case self::SORT_NAME_ASC:
                $builder->join('users', 'job_applications.candidate_id', '=', 'users.id')
                    ->orderBy('users.name', 'asc')
                    ->select('job_applications.*');
                break;
            case self::SORT_NAME_DESC:
                $builder->join('users', 'job_applications.candidate_id', '=', 'users.id')
                    ->orderBy('users.name', 'desc')
                    ->select('job_applications.*');
                break;
            case self::SORT_LATEST:
            case self::SORT_NEWEST:
            default:
                $builder->orderBy("{$table}.created_at", 'desc');
                break;
        }
    }
}
