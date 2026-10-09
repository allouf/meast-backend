<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicationFieldAnswer extends Model
{
    protected $table = 'job_application_field_answers';

    protected $fillable = ['job_application_id', 'job_template_field_id', 'value'];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function jobTemplateField(): BelongsTo
    {
        return $this->belongsTo(JobTemplateField::class);
    }
}
