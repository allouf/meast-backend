<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Services\Database\Factories\BoostPlanFactory;

class BoostPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_days',
        'base_price',
        'currency',
        'targeting',
        'is_active',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'targeting' => 'array',
        'is_active' => 'boolean',
    ];

    public function variants()
    {
        return $this->hasMany(BoostPlanVariant::class);
    }

    public function activeVariants()
    {
        return $this->variants()->where('is_active', true);
    }

    /** Search ranking uplift when this plan backs an active sponsored listing. */
    public function searchRankMultiplier(): float
    {
        $t = $this->targeting;
        if (! is_array($t) || ! isset($t['search_rank_multiplier']) || ! is_numeric($t['search_rank_multiplier'])) {
            return 1.25;
        }

        $m = (float) $t['search_rank_multiplier'];

        return $m > 0 ? $m : 1.25;
    }

    /** True when MVP placement includes the sponsored badge in search/category. */
    public function sponsoredBadgeEnabled(): bool
    {
        $t = $this->targeting;
        if (! is_array($t)) {
            return true;
        }

        return (bool) ($t['show_sponsored_badge'] ?? true);
    }
}
