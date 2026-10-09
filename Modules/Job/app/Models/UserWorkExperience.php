<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserWorkExperience extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'job_title',
        'industry',
        'description',
        'start_date',
        'end_date',
        'is_current',
        'duration_months',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
