<?php

namespace Modules\Store\Repositories\Eloquent;

use Modules\Core\Models\User;
use App\Traits\PaginateTrait;
use Illuminate\Database\Eloquent\Collection;
use Modules\Store\Enums\StoreMemberRole;
use Modules\Store\Models\StoreMember;
use Modules\Store\Repositories\Interfaces\StoreMemberRepositoryInterface;

class StoreMemberRepository implements StoreMemberRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(StoreMember $model)
    {
        $this->model = $model;
    }

    public function create(array $data): StoreMember
    {
        return $this->model->create($data);
    }

    public function findById(int $id): ?StoreMember
    {
        return $this->model->with(['user', 'inviter', 'revoker', 'store'])->findOrFail($id);
    }

    public function findByToken(string $token): ?StoreMember
    {
        return $this->model->where('invitation_token', $token)->first();
    }
    public function findTrashedById(int $id): ?StoreMember
    {
        return $this->model->onlyTrashed()->find($id);
    }

    public function findByEmailAndStore(string $email, int $storeId): ?StoreMember
    {
        return $this->model
            ->where('store_id', $storeId)
            ->first();
    }

    public function getByStore(int $storeId): Collection
    {
        return $this->model->byStore($storeId)
            ->with(['user', 'inviter', 'revoker'])
            ->get();
    }

    public function getActiveByStore(int $storeId): Collection
    {
        return $this->model->byStore($storeId)
            ->active()
            ->with(['user', 'inviter'])
            ->get();
    }

    public function getInvitedByStore(int $storeId): Collection
    {
        return $this->model->byStore($storeId)
            ->invited()
            ->with(['inviter'])
            ->get();
    }

    public function getByRoleAndStore(StoreMemberRole $role, int $storeId): Collection
    {
        return $this->model->byStore($storeId)
            ->byRole($role)
            ->with(['user', 'inviter'])
            ->get();
    }

    public function update(int $id, array $data)
    {
        $member = $this->findById($id);
        if (!$member) {
            return false;
        }
        $member->update($data);
        return $member;
    }

    public function delete(int $id): bool
    {
        $member = $this->findById($id);
        if (!$member) {
            return false;
        }

        return $member->delete();
    }
    public function restore(int $id): bool
    {
        $member = $this->findTrashedById($id);
        if (!$member) return false;

        return $member->restore();
    }

    public function revokeInvitation(int $id, int $revokedBy): bool
    {
        $member = $this->findById($id);
        if (!$member) {
            return false;
        }

        $member->revokeInvitation($revokedBy);
        return true;
    }

    public function acceptInvitation(int $id): bool
    {
        $member = $this->findById($id);
        if (!$member) {
            return false;
        }

        $member->acceptInvitation();
        return true;
    }

    public function checkEmailExistsInStore(int $userId, int $storeId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('user_id', $userId)
            ->where('store_id', $storeId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
    public function checkEmailExistsInUsers(string $email)
    {
        return User::where('email', $email)->first();
    }
    public function getStoreOwners(int $storeId): Collection
    {
        return $this->getByRoleAndStore(StoreMemberRole::STORE_OWNER, $storeId);
    }

    public function getStoreManagers(int $storeId): Collection
    {
        return $this->getByRoleAndStore(StoreMemberRole::STORE_MANAGER, $storeId);
    }

    public function getSupportAgents(int $storeId): Collection
    {
        return $this->getByRoleAndStore(StoreMemberRole::SUPPORT_AGENT, $storeId);
    }

    public function getActiveByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->active()
            ->with(['store'])
            ->get();
    }

    public function getRemovedByStore(int $storeId): Collection
    {
        return $this->model
            ->onlyTrashed()
            ->byStore($storeId)
            ->with(['user', 'inviter', 'revoker'])
            ->get();
    }

    public function all($search = null, $storeId = null, $status = null, $role = null, $paginate = true)
    {
        $query = $this->model->with(['user', 'inviter', 'revoker', 'store'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")

                    ->orWhereHas('store', function ($storeQuery) use ($search) {
                        $storeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($paginate) {
            return $this->paginate($query);
        }

        return [
            'data' => $query->get(),
            'meta' => null
        ];
    }

    public function getTrashed($search = null, $storeId = null, $status = null, $role = null, $paginate = true)
    {
        $query = $this->model->onlyTrashed()->with(['user', 'inviter', 'revoker', 'store'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")

                    ->orWhereHas('store', function ($storeQuery) use ($search) {
                        $storeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($role) {
            $query->where('role', $role);
        }

        if ($paginate) {
            return $this->paginate($query);
        }

        return [
            'data' => $query->get(),
            'meta' => null
        ];
    }
}
