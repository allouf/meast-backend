<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements JWTSubject
{
    use HasRoles;
    protected $guard_name = 'admin';
    protected $fillable = [
        'name',
        'email',
        'password',
        'image',
        'role',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }
}
