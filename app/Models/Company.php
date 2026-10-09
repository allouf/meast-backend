<?php

namespace App\Models;

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
        'cover',
        'trade_license',
        'website',
        'description',
        'gallery',
    ];

  
    protected $casts = [
        'logo'          => 'string',
        'cover'         => 'string',
        'trade_license' => 'string',
        'website'       => 'string',
        'gallery'       => 'array',
    ];

  
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(CompanyTeamMember::class)->orderBy('sort_order');
    }
}
