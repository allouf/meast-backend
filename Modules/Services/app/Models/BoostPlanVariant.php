<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BoostPlanVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'boost_plan_id',
        'country_code',
        'price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(BoostPlan::class, 'boost_plan_id');
    }
}
