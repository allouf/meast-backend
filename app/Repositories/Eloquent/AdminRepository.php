<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\AdminRepositoryInterface;
use Modules\Core\Models\Admin;

class AdminRepository implements AdminRepositoryInterface
{
    protected $admin;

    public function __construct(Admin $admin)
    {
        $this->admin = $admin;
    }

    public function all()
    {
        return $this->admin->all();
    }

    public function findById($id)
    {
        return $this->admin->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->admin->create($data);
    }

    public function update(array $data, $id)
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
