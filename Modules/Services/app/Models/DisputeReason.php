<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Services\Enums\DisputeValidationType;

// use Modules\Services\Database\Factories\DisputeReasonFactory;

class DisputeReason extends Model
{
    use HasFactory;

    protected $table = 'dispute_reasons';

    protected $fillable = [
        'name',
        'is_active',
        'validation_type'
    ];

    protected $casts = [
        'validation_type' => DisputeValidationType::class,
        'is_active' => 'boolean',
    ];

    /*
    |------------------------------------------------------------------
    | Relationships
    |------------------------------------------------------------------
    */

    public function disputes()
    {
        return $this->hasMany(ServiceOfferOrderDispute::class, 'reason_id');
    }

    /*
    |------------------------------------------------------------------
    | Scopes
    |------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |------------------------------------------------------------------
    | Helpers
    |------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
