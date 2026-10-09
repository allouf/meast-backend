<?php

namespace App\Repositories\Interfaces;

interface SliderRepositoryInterface
{
    public function all($perPage = null);

    public function findById($id);
    public function getByCategory($categoryId);
    public function create(array $data);

    public function update(array $data,$id);

    public function delete($id);
}
