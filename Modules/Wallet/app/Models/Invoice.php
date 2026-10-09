<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Wallet\Enums\InvoiceStatus;
use Modules\Wallet\Enums\PaymentRail;
use Modules\Wallet\Events\PaymentExpired;
use Modules\Wallet\Events\PaymentFailed;
use Modules\Wallet\Events\PaymentRefunded;
use Modules\Wallet\Events\PaymentSucceeded;

/**
 * One row per "I need money to flow" intent.
 *
 * Replaces the previous `WalletTopupIntent` model (same table, renamed).
 * Generic across:
 *   - Module that owns the buyable thing (via polymorphic `referenceable`).
 *   - Direction of money flow (`seller_user_id` NULL = user→platform;
 *     SET = marketplace seller-receives).
 *   - Payment rail (`rail` column; one of the PaymentRail enum cases).
 *
 * Lifecycle: see InvoiceStatus enum docblock.
 *
 * Created by `PaymentInitiatorInterface::initiate()`. Mutated only by:
 *   - The rail's webhook handler (Pending → Completed / Failed)
 *   - The expiration cron (Pending → Expired)
 *   - The refund flow (Completed → Refunded for full refunds, plus child
 *     InvoiceRefund rows for any partial)
 *
 * @property int                  $id
 * @property string               $kind                  e.g. 'wallet_topup', 'service_order'
 * @property int                  $user_id               buyer (the legacy column name; "buyer" semantically)
 * @property int|null             $seller_user_id        marketplace receiver, or NULL for user→platform
 * @property string|null          $referenceable_type    polymorphic pointer to caller's domain model
 * @property int|null             $referenceable_id
 * @property int|null             $wallet_id             buyer's platform-credit wallet, when relevant
 * @property string               $amount                gross
 * @property string               $currency
 * @property string|null          $platform_fee_amount   filled by rail when known
 * @property string|null          $seller_payout_amount  filled by rail when known
 * @property InvoiceStatus        $status
 * @property PaymentRail          $rail
 * @property string|null          $partner_reference_id  rail's primary handle (Stripe session id, etc.)
 * @property string|null          $checkout_url          NULL for instant rails
 * @property string|null          $idempotency_key       caller-provided retry-safe key
 * @property string|null          $failure_reason
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property \Illuminate\Support\Carbon|null $refunded_at
 * @property array|null           $metadata             rail-specific extras (e.g. stripe_payment_intent_id)
 */
class Invoice extends Model
{
    /**
     * Note: `user_id` is the legacy column name kept across the rename
     * for back-compat with existing data. Semantically this is the
     * buyer; the `buyer()` accessor below makes that explicit. New
     * code should use `$invoice->buyer_user_id` via that accessor or
     * call `$invoice->buyer` directly.
     */
    protected $fillable = [
        'kind',
        'user_id',
        'seller_user_id',
        'referenceable_type',
        'referenceable_id',
        'wallet_id',
        'amount',
        'currency',
        'platform_fee_amount',
        'seller_payout_amount',
        'status',
        'rail',
        'partner_reference_id',
        'checkout_url',
        'idempotency_key',
        'failure_reason',
        'completed_at',
        'expires_at',
        'failed_at',
        'refunded_at',
        'metadata',
    ];

    protected $casts = [
        'amount'                => 'decimal:8',
        'platform_fee_amount'   => 'decimal:8',
        'seller_payout_amount'  => 'decimal:8',
        'status'                => InvoiceStatus::class,
        'rail'                  => PaymentRail::class,
        'completed_at'          => 'datetime',
        'expires_at'            => 'datetime',
        'failed_at'             => 'datetime',
        'refunded_at'           => 'datetime',
        'metadata'              => 'array',
    ];

    /* ─────────────────────────── relations ─────────────────────────── */

    /** Buyer — the user paying. */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Backwards-compatible alias for any existing code that read $invoice->user. */
    public function user(): BelongsTo
    {
        return $this->buyer();
    }

    /** Marketplace seller (NULL for user→platform invoices). */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    /** The buyer's wallet (when relevant — top-ups always reference one). */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * The calling module's domain model — ServiceOrder, AdCampaign, etc.
     * Lets webhook handlers / event listeners get back to the buyable
     * thing without coupling the Wallet module to feature modules.
     */
    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    /** All refunds processed against this invoice (zero or more). */
    public function refunds(): HasMany
    {
        return $this->hasMany(InvoiceRefund::class);
    }

    /** Append-only state-change history. */
    public function auditLog(): HasMany
    {
        return $this->hasMany(InvoiceAuditLog::class)->orderBy('created_at');
    }

    /* ─────────────────────────── helpers ─────────────────────────── */

    public function isPending(): bool   { return $this->status === InvoiceStatus::Pending; }
    public function isCompleted(): bool { return $this->status === InvoiceStatus::Completed; }
    public function isRefunded(): bool  { return $this->status === InvoiceStatus::Refunded; }

    /* ─────────────────────── state transitions ─────────────────────── */

    /**
     * Transition Pending → Completed.
     *
     * Writes the audit-log row + fires PaymentSucceeded inside the same
     * DB transaction as the status update. Idempotent: calling on an
     * already-Completed invoice short-circuits silently (a webhook retry
     * may legitimately call this twice).
     *
     * @param  string|null  $partnerReferenceId  Rail's id if not already set
     *                                           (e.g. webhook delivers a
     *                                           session id we want to record).
     * @param  array<string, mixed>  $metadataPatch  Merged into existing metadata.
     */
    public function markCompleted(
        ?string $partnerReferenceId = null,
        array $metadataPatch = [],
        string $actorType = 'webhook',
        ?int $actorId = null,
    ): void {
        if ($this->status === InvoiceStatus::Completed) {
            return; // already there — webhook retry / replay
        }

        $this->guardNotTerminal();

        DB::transaction(function () use ($partnerReferenceId, $metadataPatch, $actorType, $actorId) {
            $from = $this->status;
            $merged = array_merge((array) ($this->metadata ?? []), $metadataPatch);

            $this->update([
                'status'                => InvoiceStatus::Completed->value,
                'partner_reference_id'  => $partnerReferenceId ?? $this->partner_reference_id,
                'metadata'              => $merged,
                'completed_at'          => now(),
            ]);

            $this->writeAuditEntry(
                eventType: 'status_changed',
                fromStatus: $from->value,
                toStatus:   InvoiceStatus::Completed->value,
                actorType:  $actorType,
                actorId:    $actorId,
                payload:    ['metadata_patch' => $metadataPatch],
            );

            // Fire the domain event AFTER the row + audit entry are persisted
            // (within the same transaction; if the listener throws, the whole
            // thing rolls back).
            PaymentSucceeded::dispatch($this->fresh());
        });
    }

    /**
     * Transition Pending → Failed.
     */
    public function markFailed(
        string $reason,
        string $actorType = 'webhook',
        ?int $actorId = null,
    ): void {
        if ($this->status === InvoiceStatus::Failed) {
            return;
        }
        $this->guardNotTerminal();

        DB::transaction(function () use ($reason, $actorType, $actorId) {
            $from = $this->status;
            $this->update([
                'status'         => InvoiceStatus::Failed->value,
                'failure_reason' => $reason,
                'failed_at'      => now(),
            ]);
            $this->writeAuditEntry('status_changed', $from->value, InvoiceStatus::Failed->value, $actorType, $actorId, ['reason' => $reason]);
            PaymentFailed::dispatch($this->fresh(), $reason);
        });
    }

    /**
     * Transition Pending → Expired.
     */
    public function markExpired(string $actorType = 'system', ?int $actorId = null): void
    {
        if ($this->status === InvoiceStatus::Expired) {
            return;
        }
        $this->guardNotTerminal();

        DB::transaction(function () use ($actorType, $actorId) {
            $from = $this->status;
            $this->update([
                'status' => InvoiceStatus::Expired->value,
            ]);
            $this->writeAuditEntry('status_changed', $from->value, InvoiceStatus::Expired->value, $actorType, $actorId);
            PaymentExpired::dispatch($this->fresh());
        });
    }

    /**
     * Transition Pending → Cancelled.
     */
    public function markCancelled(string $actorType = 'user', ?int $actorId = null, ?string $reason = null): void
    {
        if ($this->status === InvoiceStatus::Cancelled) {
            return;
        }
        $this->guardNotTerminal();

        DB::transaction(function () use ($actorType, $actorId, $reason) {
            $from = $this->status;
            $this->update([
                'status'         => InvoiceStatus::Cancelled->value,
                'failure_reason' => $reason,
            ]);
            $this->writeAuditEntry('status_changed', $from->value, InvoiceStatus::Cancelled->value, $actorType, $actorId, ['reason' => $reason]);
        });
    }

    /**
     * Transition Completed → Refunded (only when the refund amount equals
     * the invoice amount — partial refunds keep status=Completed).
     *
     * The InvoiceRefund row is created by the rail's refund handling code;
     * this method just records the status flip on the parent invoice and
     * fires the event.
     */
    public function markRefunded(\Modules\Wallet\Models\InvoiceRefund $refund, string $actorType = 'webhook', ?int $actorId = null): void
    {
        DB::transaction(function () use ($refund, $actorType, $actorId) {
            $from = $this->status;

            // Only flip to Refunded for full refunds. Partial refunds keep
            // the parent invoice at Completed (the refund row carries the
            // partial amount; multiple partials may exist).
            if (bccomp((string) $refund->amount, (string) $this->amount, 8) === 0) {
                $this->update([
                    'status'       => InvoiceStatus::Refunded->value,
                    'refunded_at'  => now(),
                ]);
                $this->writeAuditEntry('status_changed', $from->value, InvoiceStatus::Refunded->value, $actorType, $actorId, ['refund_id' => $refund->id]);
            } else {
                $this->writeAuditEntry('refunded', null, null, $actorType, $actorId, [
                    'refund_id'      => $refund->id,
                    'refund_amount'  => $refund->amount,
                    'kind'           => 'partial',
                ]);
            }

            PaymentRefunded::dispatch($this->fresh(), $refund);
        });
    }

    /* ─────────────────── internal helpers ─────────────────── */

    /**
     * Guard against double-transitioning a terminal invoice. Caller-side
     * idempotency already short-circuits same-state calls (e.g.
     * markCompleted on a Completed invoice); this guards against
     * out-of-order webhook noise (e.g. Failed → Completed).
     */
    private function guardNotTerminal(): void
    {
        if ($this->status->isTerminal()) {
            throw new \RuntimeException(sprintf(
                'Invoice %d is in terminal state %s and cannot be transitioned.',
                $this->id,
                $this->status->value,
            ));
        }
    }

    /**
     * Append a row to invoice_audit_log. Internal — called by the mark*()
     * methods. Caller is responsible for being inside a DB transaction.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeAuditEntry(
        string $eventType,
        ?string $fromStatus,
        ?string $toStatus,
        string $actorType,
        ?int $actorId,
        array $payload = [],
    ): void {
        InvoiceAuditLog::create([
            'invoice_id'  => $this->id,
            'event_type'  => $eventType,
            'from_status' => $fromStatus,
            'to_status'   => $toStatus,
            'actor_type'  => $actorType,
            'actor_id'    => $actorId,
            'payload'     => $payload ?: null,
            'created_at'  => now(),
        ]);
    }
}
