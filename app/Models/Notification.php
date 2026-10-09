<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Notification extends Model
{
     use HasTranslations;

    protected $fillable = ['title', 'body', 'image', 'type'];

    public $translatable = ['title', 'body'];

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }
}
