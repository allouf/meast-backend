<?php

namespace Modules\Job\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTemplateField extends Model
{
    use HasFactory;

    public const TYPE_TEXT = 'text';
    public const TYPE_NUMBER = 'number';
    public const TYPE_DATE = 'date';
    public const TYPE_TIME = 'time';
    public const TYPE_FILE = 'file';
    public const TYPE_SELECT = 'select';
    public const TYPE_MULTI_SELECT = 'multi-select';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_EMAIL = 'email';
    public const TYPE_URL = 'url';
    public const TYPE_CHECKBOX = 'checkbox';

    public static array $types = [
        'text', 'number', 'date', 'time', 'file', 'select', 'multi-select',
        'textarea', 'email', 'url', 'checkbox',
    ];

    protected $fillable = [
        'job_template_id', 'label', 'type', 'validation_rules', 'options',
        'sort_order', 'is_required',
    ];

    protected $casts = [
        'validation_rules' => 'array',
        'options' => 'array',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
    ];

    public function jobTemplate()
    {
        return $this->belongsTo(JobTemplate::class);
    }

    public static function typeSupportsOptions(string $type): bool
    {
        return in_array($type, [self::TYPE_SELECT, self::TYPE_MULTI_SELECT], true);
    }
}
