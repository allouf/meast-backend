<?php

namespace Modules\Job\Models;

use Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplicationInternalAttachment extends Model
{
    protected $table = 'job_application_internal_attachments';

    protected $fillable = [
        'job_application_id', 'user_id', 'file_path', 'original_name', 'mime_type', 'size',
    ];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
