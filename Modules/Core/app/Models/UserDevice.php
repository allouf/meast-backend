<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 'device_name', 'ip_address', 'refresh_token', 'last_seen', 'is_revoked'
    ];

    protected $casts = [
        'is_revoked' => 'boolean',
        'last_seen' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
