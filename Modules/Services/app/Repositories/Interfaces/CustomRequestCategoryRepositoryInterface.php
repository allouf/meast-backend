<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;

interface CustomRequestCategoryRepositoryInterface
{
    /** @return Collection<int, \Modules\Services\Models\CustomRequestCategory> */
    public function allOrderedByName(): Collection;
}
