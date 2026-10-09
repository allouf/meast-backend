<?php

namespace Modules\Store\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReturnItem extends Model
{
    protected $fillable = [
        'ticket_return_id',
        'order_item_id',
        'quantity',
        'refund_amount',
    ];

    public function returnRequest()
    {
        return $this->belongsTo(TicketReturn::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
