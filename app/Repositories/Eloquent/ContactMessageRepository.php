<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\ContactMessageRepositoryInterface;
use App\Models\ContactMessage;
use App\Traits\PaginateTrait;

class ContactMessageRepository implements ContactMessageRepositoryInterface
{
    use PaginateTrait;
    protected $model;

    public function __construct(ContactMessage $model)
    {
        $this->model = $model;
    }

    public function all($perPage =null)
    {
        $messages = $this->model->with('user')->latest();
        return $this->paginate($messages,$perPage);
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

    public function filter($data ,$perPage = null)
    {
        $messages = $this->model->latest()->with('user')
        ->when(isset($data['user_id']), fn($q) => $q->where('user_id', $data['user_id']))
        ->when(isset($data['subject']), fn($q) => $q->where('subject', 'like', '%' . $data['subject'] . '%'))
        ->when(isset($data['message']), fn($q) => $q->where('message', 'like', '%' . $data['message'] . '%'));
        return $this->paginate($messages,$perPage);
    }
}
