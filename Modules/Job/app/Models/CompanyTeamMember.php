<?php

namespace Modules\Job\Models;

use Modules\Core\Models\Company;
use Illuminate\Database\Eloquent\Model;

class CompanyTeamMember extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'image',
        'position',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
