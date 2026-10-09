<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Profession extends Model
{
    use HasTranslations;
    protected $fillable = ['name'];
    public array $translatable = ['name'];

    public function freelancers()
    {
        return $this->hasMany(Freelancer::class);
    }
}
