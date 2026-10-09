<?php

namespace Modules\Store\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    protected $fillable = [
        'user_id',
        'violatable_id',
        'violatable_type',
        'reason_id',
        'description',
        'severity',
        'status',
        'admin_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    public function violatable()
    {
        return $this->morphTo();
    }
    public function files()
    {
        return $this->hasMany(ViolationFile::class);
    }

    public function reason()
    {
        return $this->belongsTo(ViolationReason::class);
    }
}
