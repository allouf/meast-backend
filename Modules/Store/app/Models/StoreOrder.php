<?php

namespace Modules\Store\Models;

use Modules\Store\Models\UserAddress;
use Modules\Store\Enums\StoreOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreOrder extends Model
{
    protected $table = 'store_orders';

    protected $fillable = [
        'master_order_id',
        'store_id',
        'user_address_id',
        'subtotal',
        'shipping_method',
        'payment_method',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'status' => StoreOrderStatus::class,
    ];

    /**
     * Get the master order that owns this store order.
     */
    public function masterOrder(): BelongsTo
    {
        return $this->belongsTo(MasterOrder::class, 'master_order_id');
    }

    /**
     * Get the store that owns this order.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the user address for this order.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    /**
     * Get the order items for this store order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'store_order_id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(OrderPackage::class);
    }
}
