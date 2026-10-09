<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\StoreMemberRepositoryInterface;
use Modules\Store\Models\Store;
use Modules\Store\Models\StoreMember;
use Modules\Store\Enums\StoreMemberRole;
use Illuminate\Support\Facades\Auth;
use Exception;

class StoreMemberPermissionService
{
    protected $storeMemberRepository;
    public function __construct(StoreMemberRepositoryInterface $storeMemberRepository)
    {
        $this->storeMemberRepository = $storeMemberRepository;
    }
    /**
     * Check if current user has permission for a specific action in a store
     */
    public function hasPermission(int $storeId, string $permission): bool
    {
        $member = $this->getCurrentUserStoreMember($storeId);
        if ($member) {
        return $member->hasPermission($permission);
        }
        
        // Fallback: Check if user is the store owner via Store.user_id
        $user = Auth::user();
        if ($user) {
            $store = Store::find($storeId);
            if ($store && $store->user_id === $user->id) {
                // Store owner has all permissions
                return StoreMemberRole::STORE_OWNER->hasPermission($permission);
            }
        }
        
        return false;
    }
    /**
     * Check if current user can manage a specific role in a store
     */
    public function canManageRole(int $storeId, StoreMemberRole $role): bool
    {
        $member = $this->getCurrentUserStoreMember($storeId);
        if (!$member) {
            return false;
        }
        return $member->canManageRole($role);
    }
    /**
     * Check if current user can manage a specific member
     */
    public function canManageMember(int $storeId, int $memberId): bool
    {
        $currentMember = $this->getCurrentUserStoreMember($storeId);
        $targetMember = $this->storeMemberRepository->findById($memberId);
        if (!$targetMember) {
            $targetMember = $this->storeMemberRepository->findTrashedById($memberId);
        }
        if (!$currentMember || !$targetMember) {
            return false;
        }
        if ($currentMember->id === $targetMember->id) {
            return false;
        }
        return $currentMember->canManageMember($targetMember);
    }
    /**
     * Check if current user can invite members to a store
     */
    public function canInviteMembers(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'members.invite');
    }
    /**
     * Check if current user can remove members from a store
     */
    public function canRemoveMembers(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'members.remove');
    }
    /**
     * Check if current user can manage store settings
     */
    public function canManageStoreSettings(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'store.settings');
    }
    /**
     * Check if current user can modify product prices
     */
    public function canModifyProductPrices(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'products.pricing');
    }
    /**
     * Check if current user can manage products
     */
    public function canManageProducts(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'products.create') ||
            $this->hasPermission($storeId, 'products.update') ||
            $this->hasPermission($storeId, 'products.delete');
    }
    /**
     * Check if current user can manage inventory
     */
    public function canManageInventory(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'inventory.manage');
    }
    /**
     * Check if current user can manage orders
     */
    public function canManageOrders(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'orders.manage');
    }
    /**
     * Check if current user can view analytics
     */
    public function canViewAnalytics(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'analytics.view');
    }
    /**
     * Check if current user can manage coupons
     */
    public function canManageCoupons(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'coupons.manage');
    }
    /**
     * Check if current user can manage reviews
     */
    public function canManageReviews(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'reviews.manage');
    }
    /**
     * Check if current user has support access
     */
    public function hasSupportAccess(int $storeId): bool
    {
        return $this->hasPermission($storeId, 'support.access');
    }
    /**
     * Get current user's role in a store
     */
    public function getCurrentUserRole(int $storeId): ?StoreMemberRole
    {
        $member = $this->getCurrentUserStoreMember($storeId);
        if ($member) {
            return $member->role;
        }
        
        // Fallback: Check if user is the store owner via Store.user_id
        $user = Auth::user();
        if ($user) {
            $store = Store::find($storeId);
            if ($store && $store->user_id === $user->id) {
                return StoreMemberRole::STORE_OWNER;
            }
        }
        
        return null;
    }
    /**
     * Get current user's permissions in a store
     */
    public function getCurrentUserPermissions(int $storeId): array
    {
        $member = $this->getCurrentUserStoreMember($storeId);
        if ($member) {
            return $member->role->getPermissions();
        }
        
        // Fallback: Check if user is the store owner via Store.user_id
        $user = Auth::user();
        if ($user) {
            $store = Store::find($storeId);
            if ($store && $store->user_id === $user->id) {
                return StoreMemberRole::STORE_OWNER->getPermissions();
            }
        }
        
        return [];
    }
    /**
     * Check if current user is store owner
     */
    public function isStoreOwner(int $storeId): bool
    {
        $role = $this->getCurrentUserRole($storeId);
        return $role === StoreMemberRole::STORE_OWNER;
    }
    /**
     * Check if current user is store manager
     */
    public function isStoreManager(int $storeId): bool
    {
        $role = $this->getCurrentUserRole($storeId);
        return $role === StoreMemberRole::STORE_MANAGER;
    }
    /**
     * Check if current user is support agent
     */
    public function isSupportAgent(int $storeId): bool
    {
        $role = $this->getCurrentUserRole($storeId);
        return $role === StoreMemberRole::SUPPORT_AGENT;
    }
    /**
     * Validate that current user has required permission, throw exception if not
     */
    public function requirePermission(int $storeId, string $permission): void
    {
        if (!$this->hasPermission($storeId, $permission)) {
            abort(403, "You do not have permission to perform this action: {$permission}");
        }
    }
    /**
     * Validate that current user can manage the specified role, throw exception if not
     */
    public function requireCanManageRole(int $storeId, StoreMemberRole $role): void
    {
        if (!$this->canManageRole($storeId, $role)) {
            abort(403, "You do not have permission to assign the {$role->getDisplayName()} role");
        }
    }
    /**
     * Validate that current user can manage the specified member, throw exception if not
     */
    public function requireCanManageMember(int $storeId, int $memberId): void
    {
        if (!$this->canManageMember($storeId, $memberId)) {
            abort(403, "You do not have permission to manage this member");
        }
    }
    /**
     * Get current user's store member record (only active members)
     */
    private function getCurrentUserStoreMember(int $storeId): ?StoreMember
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        $member = $this->storeMemberRepository->findByEmailAndStore($user->email, $storeId);
        
        // Only return active members
        if ($member && $member->isActive()) {
            return $member;
        }
        
        return null;
    }
    /**
     * Get all permissions grouped by category
     */
    public function getPermissionsByCategory(): array
    {
        return [
            'Store Management' => [
                'store.manage' => 'Manage store operations',
                'store.settings' => 'Manage store settings',
            ],
            'Product Management' => [
                'products.create' => 'Create products',
                'products.read' => 'View products',
                'products.update' => 'Update products',
                'products.delete' => 'Delete products',
                'products.pricing' => 'Modify product prices',
            ],
            'Inventory Management' => [
                'inventory.manage' => 'Manage inventory',
            ],
            'Order Management' => [
                'orders.manage' => 'Manage orders',
            ],
            'Customer Management' => [
                'customers.manage' => 'Manage customers',
            ],
            'Analytics' => [
                'analytics.view' => 'View analytics',
            ],
            'Team Management' => [
                'members.invite' => 'Invite team members',
                'members.manage' => 'Manage team members',
                'members.remove' => 'Remove team members',
            ],
            'Content Management' => [
                'policies.manage' => 'Manage store policies',
                'coupons.manage' => 'Manage coupons',
                'reviews.manage' => 'Manage reviews',
            ],
            'Support' => [
                'support.access' => 'Access support features',
            ],
        ];
    }
}
