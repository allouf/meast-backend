<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserLanguage extends Model
{
    protected $fillable = ['user_id', 'language', 'level'];

    protected $casts = ['level' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
