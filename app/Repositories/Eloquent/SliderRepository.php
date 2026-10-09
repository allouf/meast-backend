<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\SliderRepositoryInterface;
use App\Models\Slider;
use App\Traits\PaginateTrait;

class SliderRepository implements SliderRepositoryInterface
{
    use PaginateTrait;
    protected $model;

    public function __construct(Slider $model)
    {
        $this->model = $model;
    }

    public function all($perPage = null)
    {
        return $this->model->latest()->get();
    }
    public function getByCategory($categoryId)
    {
        return $this->model->whereNull('category_id')->orWhere('category_id',$categoryId)->latest()->get();
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
