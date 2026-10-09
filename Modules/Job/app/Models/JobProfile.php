<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\User;

class JobProfile extends Model
{
    protected $table = 'job_profiles';

    protected $fillable = [
        'user_id',
        'name',
        'bio',
        'avatar',
        'cover',
        'gallery',
    ];

    protected $casts = [
        'gallery' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
