<?php

namespace Modules\Store\Models;

use Modules\Core\Models\User;
use Modules\Store\Enums\StoreMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoreMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'user_id',
        'role',
        'status',
        'invitation_token',
        'invited_by',
        'accepted_at',
        'revoked_at',
        'revoked_by',
    ];

    protected $casts = [
        'role' => StoreMemberRole::class,
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    // Relationships
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function revoker()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // Role checking methods
    public function isOwner(): bool
    {
        return $this->role === StoreMemberRole::STORE_OWNER;
    }

    public function isManager(): bool
    {
        return $this->role === StoreMemberRole::STORE_MANAGER;
    }

    public function isSupport(): bool
    {
        return $this->role === StoreMemberRole::SUPPORT_AGENT;
    }

    // Status checking methods
    public function isInvited(): bool
    {
        return $this->status === 'invited';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function canManageRole(StoreMemberRole $role): bool
    {
        return $this->role->canManageRole($role);
    }

    public function canManageMember(self $member): bool
    {
        return $this->role->canManageRole($member->role);
    }

    public function acceptInvitation(): void
    {
        $this->update([
            'status' => 'active',
            'accepted_at' => now(),
        ]);
    }

    public function revokeInvitation(int $revokedBy): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoked_by' => $revokedBy,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInvited($query)
    {
        return $query->where('status', 'invited');
    }

    public function scopeRevoked($query)
    {
        return $query->where('status', 'revoked');
    }

    public function scopeByRole($query, StoreMemberRole $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }
}
