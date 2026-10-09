<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobApplicationTag extends Model
{
    protected $table = 'job_application_tags';

    protected $fillable = ['job_id', 'name', 'color'];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function jobApplications(): BelongsToMany
    {
        return $this->belongsToMany(
            JobApplication::class,
            'job_application_tag_pivot',
            'job_application_tag_id',
            'job_application_id'
        );
    }
}
