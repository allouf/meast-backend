<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class SlaRule extends Model
{
    protected $fillable = [
        'store_id',
        'issue_type_id',
        'sla_response_hours',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function issueType()
    {
        return $this->belongsTo(IssueType::class);
    }
}
