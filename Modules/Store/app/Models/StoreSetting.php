<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_id',
       
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
