<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $fillable = [
        'from_url',
        'to_url',
        'status_code',
        'entity_type',
        'entity_id',
        'hits_count',
        'last_hit_at',
    ];
}
