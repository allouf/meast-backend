<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FreelancerDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'freelancer_id',
        'file_path',
        'file_type',
    ];

 
    public function freelancer()
    {
        return $this->belongsTo(Freelancer::class);
    }
}
