<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\User;

/**
 * Per-user platform-services wallet (Flow B only).
 *
 * Holds three balances in a single denomination unit (`currency`):
 *   - available  : freely spendable on platform fees
 *   - pending    : incoming funds awaiting confirmation / settlement
 *   - locked     : reserved against active subscriptions / ad commitments
 *
 * Marketplace (Flow A) does NOT use this wallet — buyer/seller
 * money flows through fiat escrow ledgers instead.
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $available_balance
 * @property string $pending_balance
 * @property string $locked_balance
 * @property string $currency
 */
class Wallet extends Model
{
    protected $fillable = [
        'user_id',
        'available_balance',
        'pending_balance',
        'locked_balance',
        'currency',
    ];

    /**
     * Decimal columns are returned as strings to preserve precision.
     * Casting to float would lose precision on large amounts.
     */
    protected $casts = [
        'available_balance' => 'decimal:8',
        'pending_balance'   => 'decimal:8',
        'locked_balance'    => 'decimal:8',
    ];

    /**
     * Wallet is owned by exactly one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Append-only audit trail of every balance change.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
