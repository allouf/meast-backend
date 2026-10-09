<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\BannerRepositoryInterface;
use App\Models\Banner;
use App\Traits\PaginateTrait;

class BannerRepository implements BannerRepositoryInterface
{
    use PaginateTrait;
    protected $model;

    public function __construct(Banner $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->latest()->get();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data,$id)
    {
        $record = $this->findById($id);
        $record->update($data);
        return $record;
    }

    public function delete($id)
    {
        $record = $this->findById($id);
        return $record->delete();
    }
}
