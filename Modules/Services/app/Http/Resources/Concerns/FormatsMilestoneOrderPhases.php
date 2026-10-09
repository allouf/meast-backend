<?php

namespace Modules\Services\Http\Resources\Concerns;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Services\Enums\MilestoneOrderPhaseStatus;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Http\Resources\User\ServiceOfferOrderPhaseResource;
use Modules\Services\Models\ServiceOfferOrder;

trait FormatsMilestoneOrderPhases
{
    /**
     * @param  'buyer'|'provider'  $viewer  Whose perspective the cursor action is resolved for.
     * @return array{milestone: array<string, mixed>|null, phases: AnonymousResourceCollection|null, cursor: array<string, mixed>|null, payment_status: string|null}
     */
    protected function milestonePhasePayload(ServiceOfferOrder $order, string $viewer = 'buyer'): array
    {
        if (! $order->isMilestoneOrder()) {
            return [
                'milestone' => null,
                'phases' => null,
                'cursor' => null,
                'payment_status' => null,
            ];
        }

        $phases = $order->relationLoaded('phases') ? $order->phases : $order->phases()->with(['latestEscrow', 'serviceCategory', 'deliveries'])->get();
        $currentId = $order->current_phase_id;

        $phases->each(function ($phase) use ($currentId) {
            $phase->setAttribute('is_cursor', (int) $phase->id === (int) $currentId);
        });

        $current = $phases->firstWhere('id', $currentId);

        return [
            'milestone' => $this->buildMilestoneHeader($order, $phases),
            'phases' => ServiceOfferOrderPhaseResource::collection($phases),
            'cursor' => $current ? $this->buildCursor($current, $viewer) : null,
            'payment_status' => $current?->paymentStatus() ?? 'unpaid',
        ];
    }

    /**
     * Milestone-level header for the order. Sourced from the order's frozen
     * milestone_snapshot (taken at define time), NOT the live template — which
     * the provider can edit afterwards. Totals are derived from the phase rows
     * so they always match what the buyer is actually paying.
     *
     * Deliberately carries NO phase/services list: `phases` is the single
     * source for that, so the response never exposes two divergent phase lists.
     *
     * @param  \Illuminate\Support\Collection<int, \Modules\Services\Models\ServiceOfferOrderPhase>  $phases
     * @return array<string, mixed>
     */
    protected function buildMilestoneHeader(ServiceOfferOrder $order, $phases): array
    {
        $snapshot = $order->milestone_snapshot ?? [];

        // Legacy fallback: orders placed before the snapshot existed and not
        // covered by the backfill read the live template (old behaviour). New
        // orders always have a snapshot, so this is rarely hit.
        $template = empty($snapshot) ? $order->milestoneTemplate() : null;

        $deliveryModel = $snapshot['delivery_model']
            ?? ($template?->delivery_model?->value ?? $template?->delivery_model);

        return [
            'title' => $snapshot['title'] ?? $template?->title,
            'description' => $snapshot['description'] ?? $template?->description,
            'delivery_model' => $deliveryModel,
            'request_modification_days' => $snapshot['request_modification_days'] ?? $template?->request_modification_days,
            'total_budget' => (float) $phases->sum('budget'),
            'duration_days' => (int) $phases->sum('duration_days'),
            'currency' => $order->currency,
        ];
    }

    /**
     * @param  'buyer'|'provider'  $viewer
     * @return array<string, mixed>
     */
    protected function buildCursor($phase, string $viewer = 'buyer'): array
    {
        $status = $phase->status;
        $funded = $phase->isFunded();

        return [
            'phase_id' => $phase->id,
            'phase_name' => $phase->service_name,
            'sort_order' => $phase->sort_order,
            'status' => $status?->value ?? $status,
            'payment_status' => $phase->paymentStatus(),
            'action' => $this->resolvePhaseAction($status, $funded, $viewer),
        ];
    }

    /**
     * Resolve the next call-to-action for the current phase from the viewer's
     * perspective.
     *
     * A milestone phase alternates between the two parties, so the same phase
     * state implies a different action for each side: a funded-but-not-started
     * phase is the provider's cue to "start" while the buyer is merely
     * "awaiting_provider"; a paid phase is never the buyer's cue to pay again.
     * The frontend maps its own role's action 1:1 to a button, so the buyer
     * never receives a provider-only action (and vice versa). "awaiting_*"
     * values are non-actionable status hints for the waiting party.
     *
     * @param  'buyer'|'provider'  $viewer
     */
    protected function resolvePhaseAction(?MilestoneOrderPhaseStatus $status, bool $funded, string $viewer): string
    {
        $isProvider = $viewer === 'provider';

        return match (true) {
            $status === MilestoneOrderPhaseStatus::PendingPayment && ! $funded
                => $isProvider ? 'awaiting_payment' : 'pay',
            $status === MilestoneOrderPhaseStatus::PendingPayment && $funded
                => $isProvider ? 'start' : 'awaiting_provider',
            $status === MilestoneOrderPhaseStatus::InProgress
                => $isProvider ? 'deliver' : 'awaiting_delivery',
            $status === MilestoneOrderPhaseStatus::Delivered
                => $isProvider ? 'awaiting_review' : 'review',
            $status === MilestoneOrderPhaseStatus::InRevision
                => $isProvider ? 'deliver_revision' : 'awaiting_provider',
            default => 'none',
        };
    }
}
