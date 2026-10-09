<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Slider extends Model
{
    use HasTranslations;

    public array $translatable = ['title','sub_title'];

    protected $fillable = ['category_id','title' ,'sub_title','image'];
}
