<?php

namespace Modules\Community\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class PostLog extends Model
{
    protected $fillable = ['post_id', 'user_id', 'changes'];

    protected $casts = [
        'changes' => 'array',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
