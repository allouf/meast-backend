<?php

namespace App\Repositories\Interfaces;

interface ContactMessageRepositoryInterface
{
    public function all($perPage =null);

    public function findById($id);

    public function create(array $data);

    public function update(array $data,$id);

    public function delete($id);

    public function filter(array $data ,$perPage =null);

}
