<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class StorePolicy extends Model
{
    protected $fillable = [
        'store_id','type','content'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
