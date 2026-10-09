<?php

namespace App\Services;

use App\Utilities\FileManager;
use App\Repositories\Interfaces\AdminRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class AdminService
{
    protected $adminRepository;
    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }
    public function all()
    {
        return $this->adminRepository->all();
    }
    public function findById($id)
    {
        return $this->adminRepository->findById($id);
    }
    public function create(array $data)
    {
        $data['password'] = Hash::make($data['password']);
        if (isset($data['image'])) {
            $data['image'] = FileManager::upload('users', $data['image']);
        }
        return $this->adminRepository->create($data);
    }
    public function update(array $data, $id)
    {
        $admin = $this->adminRepository->findById($id);
        if (isset($data['image'])) {
            if($admin->image && FileManager::fileExists($admin->image))
            {
            FileManager::delete($admin->image);
            }
            $data['image'] = FileManager::upload('users', $data['image']);
        }
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->adminRepository->update($data, $id);
    }
    public function delete($id)
    {
        $admin = $this->adminRepository->findById($id);
        if($admin->image && FileManager::fileExists($admin->image))
            {
            FileManager::delete($admin->image);
            }
        return $this->adminRepository->delete($id);
    }
    public function updateProfile(array $data, $id)
    {

        if (isset($data['image'])) {
            $admin = $this->adminRepository->findById($id);
            if($admin->image && FileManager::fileExists($admin->image))
            {
            FileManager::delete($admin->image);
            }
            $data['image'] = FileManager::upload('users', $data['image']);
        }
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return $this->adminRepository->update($data,$id);
    }
}
