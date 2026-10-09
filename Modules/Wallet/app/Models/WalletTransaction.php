<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Wallet\Enums\WalletTransactionType;

/**
 * Append-only audit row for every change to a Wallet's balances.
 *
 * Records the change type, the amount, the balance before and after,
 * an optional polymorphic reference to the source entity (Order, Payment,
 * Refund, etc.), and free-form metadata as JSON.
 *
 * @property int                                   $id
 * @property int                                   $wallet_id
 * @property WalletTransactionType                 $type
 * @property string                                $amount
 * @property string                                $balance_before
 * @property string                                $balance_after
 * @property string|null                           $reference_type
 * @property int|null                              $reference_id
 * @property array|null                            $metadata
 */
class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected $casts = [
        'type'           => WalletTransactionType::class,
        'amount'         => 'decimal:8',
        'balance_before' => 'decimal:8',
        'balance_after'  => 'decimal:8',
        'metadata'       => 'array',
    ];

    /**
     * The wallet this transaction modified.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Polymorphic source of the transaction (Order, Payment, etc.).
     * Both reference_type and reference_id are nullable, so this
     * relationship returns null for manual adjustments.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
