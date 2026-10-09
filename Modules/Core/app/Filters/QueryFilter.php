<?php

namespace Modules\Core\Filters;


use Illuminate\Database\Eloquent\Builder;

abstract class QueryFilter
{
    public function __construct(
        protected array $filters = []
    ) {}

    /**
     * Apply filters to the query.
     */
    abstract public function apply(Builder $builder): Builder;

    protected function value(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    protected function filled(string $key): bool
    {
        $v = $this->filters[$key] ?? null;
        return $v !== null && $v !== '';
    }
}
