<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'registration_no',
        'vat_tax_id',
        'logo',
        'trade_license',
        'website',
    ];

    protected $casts = [
        'logo' => 'string',
        'trade_license' => 'string',
        'website' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(\Modules\Core\Models\User::class);
    }

    /** Job profile (name, bio, avatar, cover, gallery) for this company's user. */
    public function jobProfile()
    {
        return $this->hasOne(\Modules\Job\Models\JobProfile::class, 'user_id', 'user_id');
    }

    public function teamMembers()
    {
        return $this->hasMany(\Modules\Job\Models\CompanyTeamMember::class)->orderBy('sort_order');
    }
}
