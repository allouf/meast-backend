<?php

namespace Modules\Store\Policies;

use Modules\Store\Models\Product;
use Modules\Core\Models\User;
use Modules\Store\Services\StoreMemberPermissionService;
use Illuminate\Auth\Access\Response;

/**
 * @deprecated This policy is deprecated. Use StoreMemberPermissionService instead.
 * This policy is kept for backward compatibility but will be removed in future versions.
 */
class ProductPolicy
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
        return true; // Users can view products from their stores
    }

    /**
     * Determine whether the user can view the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'products.read') instead
     */
    public function view(User $user, Product $product): bool
    {
        try {
            return $this->permissionService->hasPermission($product->store_id, 'products.read');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $product->store->user_id;
        }
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create products
    }

    /**
     * Determine whether the user can update the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'products.update') instead
     */
    public function update(User $user, Product $product): bool
    {
        try {
            return $this->permissionService->hasPermission($product->store_id, 'products.update');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $product->store->user_id;
        }
    }

    /**
     * Determine whether the user can delete the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'products.delete') instead
     */
    public function delete(User $user, Product $product): bool
    {
        try {
            return $this->permissionService->hasPermission($product->store_id, 'products.delete');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $product->store->user_id;
        }
    }

    /**
     * Determine whether the user can restore the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'products.update') instead
     */
    public function restore(User $user, Product $product): bool
    {
        try {
            return $this->permissionService->hasPermission($product->store_id, 'products.update');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $product->store->user_id;
        }
    }

    /**
     * Determine whether the user can permanently delete the model.
     * @deprecated Use StoreMemberPermissionService::hasPermission($storeId, 'products.delete') instead
     */
    public function forceDelete(User $user, Product $product): bool
    {
        try {
            return $this->permissionService->hasPermission($product->store_id, 'products.delete');
        } catch (\Exception $e) {
            // Fallback to original logic for backward compatibility
            return $user->id === $product->store->user_id;
        }
    }
}
