<?php

namespace Modules\Core\Repositories\Eloquent;

use Modules\Core\Models\User;
use App\Traits\PaginateTrait;
use Illuminate\Http\Request;
use Modules\Core\Filters\UserFilter;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getConsumers()
    {
        $query = $this->model->whereHas('roles', function ($q) {
            $q->where('name', 'consumer');
        });
        return $this->paginate($query);
    }

    public function getDetails(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function getAllUsers()
    {
        $users = $this->model->where('type', 'user')->with('posts')->latest();
        return $this->paginate($users);
    }

    public function noPaginationUsers()
    {
        return $this->model->where('type', 'user')->latest()->get();
    }

    public function findById($id)
    {
        return $this->model->with('company')->findOrFail($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->with('company')->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return $this->model->where('phone', $phone)->with('company')->first();
    }

    public function markVerified(User $user): void
    {
        $user->update(['verified_at' => now()]);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(User $user, array $data)
    {
        $user->update($data);
        return $user;
    }

    public function delete($id)
    {
        $record = $this->findById($id);
        return $record->delete();
    }

    public function switchActivation($id)
    {
        $record = $this->findById($id);
        $record->is_active = !$record->is_active;
        $record->save();
    }

    public function filter(Request $request)
    {
        $builder = (new UserFilter($request))->apply(User::query());
        $users = $builder->with(['company', 'roles'])->latest();
        return $this->paginate($users);
    }

    public function updatePassword(User $user, string $newPassword)
    {
        $user->password = $newPassword;
        return $user->save();
    }

    public function updateRole($user, string $role): void
    {
        $user->syncRoles([$role]);
    }
}
