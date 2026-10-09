<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\User;

/**
 * Represents a seller's Stripe Connect account (Express type for v1).
 *
 * One row per platform user that has registered as a seller. Lifecycle
 * managed jointly by:
 *   - StripeOnboardingService.createForUser() — creates row + Stripe account.
 *   - account.updated webhook handler — syncs charges/payouts/details flags.
 *   - StripeConnectRail::supports() — reads charges_enabled to gate marketplace
 *     invoices.
 *
 * @property int          $id
 * @property int          $user_id
 * @property string       $stripe_account_id    'acct_xxx' from Stripe
 * @property bool         $details_submitted    KYB form completed by seller
 * @property bool         $charges_enabled      Stripe will accept charges destined here
 * @property bool         $payouts_enabled      Stripe will pay out earned funds
 * @property string|null  $country
 * @property string|null  $default_currency
 * @property string       $account_type         'express' | 'standard' | 'custom'
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 */
class SellerStripeAccount extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_account_id',
        'details_submitted',
        'charges_enabled',
        'payouts_enabled',
        'country',
        'default_currency',
        'account_type',
        'last_synced_at',
    ];

    protected $casts = [
        'details_submitted' => 'boolean',
        'charges_enabled'   => 'boolean',
        'payouts_enabled'   => 'boolean',
        'last_synced_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Can this account RECEIVE charges as a Connect destination?
     *
     * `charges_enabled` is the only flag that strictly gates accepting
     * a charge — Stripe will refuse the destination charge otherwise.
     * `details_submitted` and `payouts_enabled` are softer signals
     * (the seller may not yet be able to withdraw, but charges can land).
     *
     * For our use case we require all three so we never park funds in
     * a Connect account the seller can't immediately withdraw from.
     */
    public function isReadyForCharges(): bool
    {
        return $this->details_submitted
            && $this->charges_enabled
            && $this->payouts_enabled;
    }

    /**
     * Pre-flight assertion: throw an actionable 422 if the user identified
     * by $userId hasn't completed Stripe Connect onboarding. Mirrors the
     * gate in StripeConnectRail::supports() but surfaces a clear error
     * message at the call site instead of bubbling up the generic
     * NoSupportedRailException from the rail resolver.
     *
     * Called by Services module (single-offer createOrder, milestone phase
     * pay) and any future caller that mints a Stripe Connect invoice.
     */
    public static function assertReadyForCharges(int $userId): void
    {
        $account = self::query()->where('user_id', $userId)->first();

        if ($account === null || ! $account->isReadyForCharges()) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(
                422,
                'This seller has not completed payment onboarding yet. They must finish Stripe Connect onboarding before accepting orders.',
            );
        }
    }
}
