<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Services\Database\Factories\ServiceOfferOrderExtensionReasonFactory;

class ServiceOfferOrderExtensionReason extends Model
{
    use HasFactory;

 protected $table = 'service_offer_order_extension_reasons';

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function extensions()
    {
        return $this->hasMany(ServiceOfferOrderExtension::class, 'reason_id');
    }
}
