<?php

namespace Modules\Advertisement\Repositories\Interfaces;

interface AdvertisementRepositoryInterface
{
    public function all($filters = []);

    public function find($id);

    public function findBySlug($slug);

    public function create($data);

    public function update($id, $data);

    public function delete($id);

    public function getByUser($userId, $filters = []);

    public function getByCategory($categoryId, $filters = []);

    public function getBySubcategory($subcategoryId, $filters = []);

    public function getActive($filters = []);

    public function search($query, $filters = []);

    public function getSimilar($advertisementId, $categoryId, $limit = 10);

    public function incrementViews($id);

    public function getExpired();

    public function markAsExpired($id);
}
