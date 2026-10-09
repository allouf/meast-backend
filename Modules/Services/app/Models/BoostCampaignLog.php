<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\Admin;

// use Modules\Services\Database\Factories\BoostCampaignLogFactory;

class BoostCampaignLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'boost_campaign_id',
        'admin_id',
        'action',
        'reason',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(BoostCampaign::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}