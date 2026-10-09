<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountUpgrade extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'is_used'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
