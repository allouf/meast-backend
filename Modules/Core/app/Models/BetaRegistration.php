<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class BetaRegistration extends Model
{
    protected $fillable = [
        'name',
        'email',
        'is_business_user',
        'phone',
        'registering_as',
        'country',
        'business_type',
        'industry_categories',
        'selling_online',
        'interested_in',
        'structured_feedback',
        'username',
        'password',
    ];

    protected $casts = [
        'is_business_user' => 'boolean',
        'industry_categories' => 'array',
        'interested_in' => 'array',
    ];
}
