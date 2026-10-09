<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class General extends Model
{
    protected $fillable = ['key', 'value', 'group'];
}
