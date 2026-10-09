<?php

namespace Modules\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Enums\PaymentRail;

/**
 * One row per webhook delivery received from any payment rail.
 *
 * Used as the dedup anchor: webhook handlers attempt INSERT keyed on
 * (rail, provider_event_id); a unique-constraint violation indicates a
 * duplicate delivery and the handler ack-200's without re-processing.
 *
 * Decoupled from `invoices.partner_reference_id` because some webhook
 * events don't reference an invoice at all (Stripe Connect's
 * account.updated, for example, indicates a seller's onboarding state
 * has changed without any invoice involvement).
 *
 * @property int                $id
 * @property string             $rail              one of PaymentRail::values()
 * @property string             $provider_event_id rail's id for this delivery
 * @property string|null        $event_type        e.g. 'checkout.session.completed'
 * @property \Illuminate\Support\Carbon       $received_at
 * @property \Illuminate\Support\Carbon|null  $processed_at
 * @property array|null         $payload
 */
class WebhookEvent extends Model
{
    /**
     * No `updated_at`. We use `received_at` and `processed_at` to track
     * the lifecycle; nothing else mutates a row after the initial insert
     * + the single processing-success update.
     */
    public $timestamps = false;

    protected $fillable = [
        'rail',
        'provider_event_id',
        'event_type',
        'received_at',
        'processed_at',
        'payload',
    ];

    protected $casts = [
        'received_at'  => 'datetime',
        'processed_at' => 'datetime',
        'payload'      => 'array',
    ];

    /**
     * Mark this event as processed. Called by the rail's webhook handler
     * after the business logic has run successfully.
     */
    public function markProcessed(): void
    {
        $this->processed_at = now();
        $this->save();
    }
}
