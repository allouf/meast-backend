<?php

namespace Modules\Advertisement\Repositories\Interfaces;

interface AdvertisementSubcategoryRepositoryInterface
{
    public function all($filters = []);

    public function find($id);

    public function findBySlug($slug);

    public function create($data);

    public function update($id, $data);

    public function delete($id);

    public function getByCategory($categoryId);

    public function getActive($filters = []);

    public function hasAdvertisements(int $subcategoryId): bool;

    public function toggleStatus(int $id);
}
