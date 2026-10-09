<?php

namespace Modules\Store\Policies;

use Modules\Store\Models\Store;
use Modules\Core\Models\User;
use Modules\Store\Services\StoreMemberPermissionService;
use Illuminate\Auth\Access\Response;

/**
 * @deprecated This policy is deprecated. Use StoreMemberPermissionService instead.
 * This policy is kept for backward compatibility but will be removed in future versions.
 */
class StorePolicy
{
    protected $permissionService;

    public function __construct(StoreMemberPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own stores
    }

    /**
     * Determine whether the user can view the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'store.manage') instead
     */
    public function view(User $user, Store $store): bool
    {
        try {
            return $this->permissionService->hasPermission($store->id, 'store.manage');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $store->user_id;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create a store
    }

    /**
     * Determine whether the user can update the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'store.settings') instead
     */
    public function update(User $user, Store $store): bool
    {
        try {
            return $this->permissionService->hasPermission($store->id, 'store.settings');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $store->user_id;
        }
    }

    /**
     * Determine whether the user can delete the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'store.settings') instead
     */
    public function delete(User $user, Store $store): bool
    {
        try {
            return $this->permissionService->hasPermission($store->id, 'store.settings');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $store->user_id;
        }
    }

    /**
     * Determine whether the user can restore the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'store.settings') instead
     */
    public function restore(User $user, Store $store): bool
    {
        try {
            return $this->permissionService->hasPermission($store->id, 'store.settings');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $store->user_id;
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'store.settings') instead
     */
    public function forceDelete(User $user, Store $store): bool
    {
        try {
            return $this->permissionService->hasPermission($store->id, 'store.settings');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $store->user_id;
        }
    }
}
