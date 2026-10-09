<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\User;

// use Modules\Services\Database\Factories\ServiceProviderBlockFactory;

class ServiceProviderBlock extends Model
{
    use HasFactory;

    protected $table = 'service_provider_blocks';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'service_provider_id',
        'buyer_id',

    ];


    public function provider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // protected static function newFactory(): ServiceProviderBlockFactory
    // {
    //     // return ServiceProviderBlockFactory::new();
    // }
}
