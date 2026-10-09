<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class ViolationFile extends Model
{
    protected $fillable = ['violation_id', 'file_path', 'type'];
    public function violation()
    {
        return $this->belongsTo(Violation::class);
    }
}
