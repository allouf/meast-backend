<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Job\Enums\EmploymentType;
use Modules\Job\Enums\ExperienceLevel;
use Modules\Job\Enums\JobStatus;
use Modules\Job\Enums\WorkMode;

/** Job posting model. Uses table "job_postings". */
class Job extends Model
{
    use HasFactory;

    protected $table = 'job_postings';

    protected $fillable = [
        'title',
        'employment_type',
        'experience_level',
        'country_code',
        'region',
        'work_mode',
        'description',
        'salary_min',
        'salary_max',
        'currency',
        'hide_salary',
        'application_deadline',
        'status',
        'application_link',
        'job_template_id',
        'employer_id',
        'category_id',
    ];

    protected $casts = [
        'employment_type' => EmploymentType::class,
        'experience_level' => ExperienceLevel::class,
        'work_mode' => WorkMode::class,
        'status' => JobStatus::class,
        'hide_salary' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'application_deadline' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function jobTemplate()
    {
        return $this->belongsTo(JobTemplate::class, 'job_template_id');
    }

    public function applications()
    {
        return $this->hasMany(JobApplication::class);
    }

    public function latestApplications()
    {
        return $this->hasMany(JobApplication::class)
            ->orderByDesc('created_at')
            ->limit(3);
    }
}
