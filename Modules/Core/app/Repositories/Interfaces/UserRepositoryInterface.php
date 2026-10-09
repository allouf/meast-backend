<?php

namespace Modules\Core\Repositories\Interfaces;

use Modules\Core\Models\User;
use Illuminate\Http\Request;

interface UserRepositoryInterface
{
    public function getConsumers();
    public function getDetails(int $id);
    public function getAllUsers();
    public function noPaginationUsers();
    public function findById($id);
    public function findByEmail(string $email);
    public function findByPhone(string $phone);
    public function markVerified(User $user);
    public function create(array $data);
    public function update(User $user, array $data);
    public function delete($id);
    public function updatePassword(User $user, string $newPassword);
    public function switchActivation($id);
    public function filter(Request $request);
    public function updateRole($user, string $role);
}
