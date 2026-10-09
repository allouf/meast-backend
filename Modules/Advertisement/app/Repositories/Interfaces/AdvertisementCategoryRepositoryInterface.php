<?php

namespace Modules\Advertisement\Repositories\Interfaces;

interface AdvertisementCategoryRepositoryInterface
{
    public function all($filters = []);

    public function find($id);

    public function findBySlug($slug);

    public function create($data);

    public function update($id, $data);

    public function delete($id);

    public function getActive($filters = []);

    public function getWithSubcategories();

    public function hasAdvertisements(int $categoryId): bool;

    public function toggleStatus(int $id);
}
