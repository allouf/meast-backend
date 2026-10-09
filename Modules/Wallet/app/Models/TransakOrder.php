<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * Represents a single Transak crypto-purchase we've been notified about.
 *
 * Created/updated exclusively by the Transak webhook handler — this is a
 * read-only record from the platform's perspective. The actual money/crypto
 * never flows through us; this is just our local mirror of what Transak has
 * told us happened.
 */
class TransakOrder extends Model
{
    protected $fillable = [
        'transak_order_id',
        'partner_order_id',
        'user_id',
        'status',
        'fiat_currency',
        'fiat_amount',
        'crypto_currency',
        'crypto_amount',
        'network',
        'wallet_address',
        'payload',
    ];

    protected $casts = [
        'payload'         => 'array',
        'fiat_amount'     => 'decimal:8',
        'crypto_amount'   => 'decimal:18',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
