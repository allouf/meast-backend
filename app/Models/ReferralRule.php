<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralRule extends Model
{
    protected $fillable = [
        'action_type',
        'points',
    ];
}
