<?php

namespace Modules\Store\Repositories\Interfaces;

interface CategoryRepositoryInterface
{
    public function all($search = null, $paginate = true);
    public function getActive(?string $search = null, ?string $sort = null,bool $paginate = true);
    public function getWithChildrens();
    public function findById($id);
    public function create(array $data);
    public function update(array $data, $id);
    public function delete($id);
    public function switchActivation($id);
    public function getMaxSortOrderByParent($id);
    public function getSiblings($parentId);
    public function updateSortOrder($id, $sortOrder);
}
