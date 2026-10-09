<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Collection;
use Modules\Services\Models\CustomRequestCategory;
use Modules\Services\Repositories\Interfaces\CustomRequestCategoryRepositoryInterface;

class CustomRequestCategoryRepository implements CustomRequestCategoryRepositoryInterface
{
    public function __construct(
        protected CustomRequestCategory $model
    ) {}

    public function allOrderedByName(): Collection
    {
        return $this->model->newQuery()->orderBy('name')->get();
    }
}
