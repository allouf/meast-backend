<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserLink extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'label',
        'url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
