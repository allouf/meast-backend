<?php

namespace Modules\Store\Models;

use Modules\Core\Models\User;
use Modules\Store\Enums\MasterOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterOrder extends Model
{
    protected $table = 'master_orders';

    protected $fillable = [
        'user_id',
        'total_price',
        'status',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'status' => MasterOrderStatus::class,
    ];

    /**
     * Get the user that owns the master order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the store orders for this master order.
     */
    public function storeOrders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'master_order_id');
    }
}
