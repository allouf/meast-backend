<?php

namespace Modules\Store\Mail;

use Modules\Store\Models\StoreMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StoreInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StoreMember $member, public string $inviteUrl) {}

    public function build()
    {
        $store = $this->member->store;
        $inviter = $this->member->inviter;

        return $this->subject(__('store::messages.You are invited to join :store as :role', [
            'store' => $store->name,
            'role' => $this->member->role->getDisplayName()
        ]))
            ->view('emails.store_invitation')
            ->with([
                'store' => $store,
                'member' => $this->member,
                'role' => $this->member->role,
                'roleDisplay' => $this->member->role->getDisplayName(),
                'permissions' => $this->member->role->getPermissions(),
                'permissionsByCategory' => $this->getPermissionsByCategory(),
                'inviteUrl' => $this->inviteUrl,
                'inviter' => $inviter,
                'invitedAt' => $this->member->created_at,
            ]);
    }

    /**
     * Group permissions by category for better display
     */
    private function getPermissionsByCategory(): array
    {
        $permissions = $this->member->role->getPermissions();

        $categories = [
            'Store Management' => ['store.manage', 'store.settings'],
            'Product Management' => ['products.create', 'products.read', 'products.update', 'products.delete', 'products.pricing'],
            'Inventory Management' => ['inventory.manage'],
            'Order Management' => ['orders.manage'],
            'Customer Management' => ['customers.manage'],
            'Analytics' => ['analytics.view'],
            'Team Management' => ['members.invite', 'members.manage', 'members.remove'],
            'Content Management' => ['policies.manage', 'coupons.manage', 'reviews.manage'],
            'Support' => ['support.access'],
        ];

        $result = [];
        foreach ($categories as $category => $categoryPermissions) {
            $userPermissions = array_intersect($permissions, $categoryPermissions);
            if (!empty($userPermissions)) {
                $result[$category] = $userPermissions;
            }
        }

        return $result;
    }
}
