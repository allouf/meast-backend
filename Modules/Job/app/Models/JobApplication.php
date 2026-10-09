<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Job\Enums\ApplicationStatus;

class JobApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id', 'job_id', 'status', 'resume_path', 'cover_letter',
        'rejection_reason', 'rating','rating_note',
    ];

    protected $casts = [
        'status' => ApplicationStatus::class,
        'rating' => 'integer',
    ];

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function notes()
    {
        return $this->hasMany(JobApplicationNote::class);
    }

    public function tags()
    {
        return $this->belongsToMany(
            JobApplicationTag::class,
            'job_application_tag_pivot',
            'job_application_id',
            'job_application_tag_id'
        );
    }

    public function internalAttachments()
    {
        return $this->hasMany(JobApplicationInternalAttachment::class);
    }

    public function fieldAnswers()
    {
        return $this->hasMany(JobApplicationFieldAnswer::class);
    }
}
