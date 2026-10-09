<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Enums\StoreMemberRole;
use Modules\Store\Models\StoreMember;
use Illuminate\Database\Eloquent\Collection;

interface StoreMemberRepositoryInterface
{
    public function create(array $data): StoreMember;

    public function findById(int $id): ?StoreMember;
    public function findTrashedById(int $id);

    public function findByToken(string $token): ?StoreMember;

    public function findByEmailAndStore(string $email, int $storeId): ?StoreMember;

    public function getByStore(int $storeId): Collection;

    public function getActiveByStore(int $storeId): Collection;

    public function getInvitedByStore(int $storeId): Collection;

    public function getByRoleAndStore(StoreMemberRole $role, int $storeId): Collection;

    public function update(int $id, array $data);

    public function delete(int $id): bool;
    public function restore(int $id): bool;

    public function revokeInvitation(int $id, int $revokedBy): bool;

    public function acceptInvitation(int $id): bool;

    public function checkEmailExistsInStore(int $userId, int $storeId, ?int $excludeId = null): bool;
    public function checkEmailExistsInUsers(string $email);
    public function getStoreOwners(int $storeId): Collection;

    public function getStoreManagers(int $storeId): Collection;

    public function getSupportAgents(int $storeId): Collection;

    public function getActiveByUser(int $userId): Collection;

    public function getRemovedByStore(int $storeId): Collection;

    public function all($search = null, $storeId = null, $status = null, $role = null, $paginate = true);

    public function getTrashed($search = null, $storeId = null, $status = null, $role = null, $paginate = true);
}
