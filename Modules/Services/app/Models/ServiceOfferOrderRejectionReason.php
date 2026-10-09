<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Services\Database\Factories\ServiceOfferOrderRejectionReasonFactory;

class ServiceOfferOrderRejectionReason extends Model
{
    use HasFactory;


    protected $fillable = [
        // 'key',
        'name',
        'is_active',
        'sort_order'
    ];


}
