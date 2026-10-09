<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;


class IssueType extends Model
{
    use HasTranslations;

    protected $fillable = [
        'name',
        'description',
        'priority',
    ];

    public $translatable = ['name', 'description'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function slaRule()
    {
        return $this->hasOne(SlaRule::class);
    }
}
