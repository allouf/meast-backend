<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = ['notification_id', 'is_read', 'user_id'];

    protected $casts = [
        'is_read' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }
}
