<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralLog extends Model
{
    protected $fillable = [
        'referrer_id',
        'referred_user_id',
        'action',
        'action_data',
        'points_earned',
        'status',
    ];

    protected $casts = [
        'action_data' => 'array',
    ];

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
