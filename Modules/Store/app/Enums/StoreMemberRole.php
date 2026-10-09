<?php

namespace Modules\Store\Enums;

enum StoreMemberRole: string
{
    case STORE_OWNER = 'STORE_OWNER';
    case STORE_MANAGER = 'STORE_MANAGER';
    case SUPPORT_AGENT = 'SUPPORT_AGENT';

    /**
     * Get the display name for the role
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::STORE_OWNER => 'Store Owner',
            self::STORE_MANAGER => 'Store Manager',
            self::SUPPORT_AGENT => 'Support Agent',
        };
    }

    /**
     * Get the description for the role
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::STORE_OWNER => 'Full administrative access to all store operations and settings. Can manage products, orders, inventory, team members, and all store configurations.',
            self::STORE_MANAGER => 'Day-to-day operations management with limited settings access. Can manage products, orders, and inventory, but cannot modify critical store settings or manage team members.',
            self::SUPPORT_AGENT => 'Read-only access for customer support purposes. Limited to viewing products, orders, customers, and reviews. Cannot make any modifications.',
        };
    }

    /**
     * Get the permissions for this role
     */
    public function getPermissions(): array
    {
        return match ($this) {
            self::STORE_OWNER => [
                'store.manage',
                'store.settings',
                'products.create',
                'products.read',
                'products.update',
                'products.delete',
                'products.pricing',
                'inventory.manage',
                'orders.manage',
                'customers.manage',
                'analytics.view',
                'members.invite',
                'members.manage',
                'members.remove',
                'policies.manage',
                'coupons.manage',
                'reviews.manage',
            ],
            self::STORE_MANAGER => [
                'store.manage',
                'products.create',
                'products.read',
                'products.update',
                'products.pricing',
                'inventory.manage',
                'orders.manage',
                'customers.manage',
                'analytics.view',
                'members.invite',
                'policies.manage',
                'coupons.manage',
                'reviews.manage',
            ],
            self::SUPPORT_AGENT => [
                'products.read',
                'orders.read',
                'customers.read',
                'reviews.read',
                'support.access',
            ],
        };
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissions());
    }

    /**
     * Get all available roles
     */
    public static function getAllRoles(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get role hierarchy level (higher number = more permissions)
     */
    public function getHierarchyLevel(): int
    {
        return match ($this) {
            self::STORE_OWNER => 3,
            self::STORE_MANAGER => 2,
            self::SUPPORT_AGENT => 1,
        };
    }

    /**
     * Check if this role can manage another role
     */
    public function canManageRole(StoreMemberRole $otherRole): bool
    {
        return $this->getHierarchyLevel() > $otherRole->getHierarchyLevel();
    }
}
