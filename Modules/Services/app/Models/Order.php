<?php

namespace Modules\Services\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;
use Modules\Services\Enums\CustomRequestOrderPaymentStatus;

/**
 * Contract / payment record for an accepted custom-request quote (RFQ flow).
 */
class Order extends Model
{
    use HasFactory;

    protected $table = 'custom_request_orders';

    protected $fillable = [
        'quote_id',
        'client_id',
        'provider_id',
        'service_price',
        'vat_amount',
        'fees_amount',
        'total_amount',
        'payment_method',
        'payment_status',
    ];

    protected $casts = [
        'service_price' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'payment_status' => CustomRequestOrderPaymentStatus::class,
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class, 'provider_id');
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->provider();
    }

}
