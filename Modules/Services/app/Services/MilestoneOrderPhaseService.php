<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\MilestoneOrderPhaseStatus;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Wallet\Enums\EscrowStatus;
use Modules\Services\Models\Milestone;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Services\Models\ServiceOfferOrderPhase;
use Modules\Services\Models\ServiceOfferOrderRevision;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;
use Modules\Wallet\Contracts\PaymentInitiatorInterface;
use Modules\Wallet\Models\SellerStripeAccount;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MilestoneOrderPhaseService
{
    public function __construct(
        protected ServiceOfferOrderRepositoryInterface $orderRepository,
        protected PaymentInitiatorInterface $payments,
        protected MilestoneService $milestoneService,
    ) {}

    /**
     * Provider-side: define the milestone template on a status=Negotiation
     * order. This is the bridge between the buyer's initial "I want to
     * negotiate this offer into milestone-pay" request and the per-phase
     * payment flow that already works.
     *
     * Atomic transaction (all-or-nothing):
     *   1. Lock the order and verify ownership + status=Negotiation.
     *   2. Create a Milestone template through MilestoneService (becomes
     *      part of the provider's library — reusable later, listed on
     *      their templates dashboard).
     *   3. Use createPhasesFromTemplate() to copy the template's lines
     *      into service_offer_order_phases (each per-phase escrow flow
     *      already works against these rows).
     *   4. Point the order at the template via orderable_type/orderable_id.
     *   5. Flip status Negotiation -> PendingPayment so the existing
     *      cursor + Pay endpoints take over.
     *
     * @param  array<string, mixed>  $data  Same payload shape as StoreMilestoneRequest.
     */
    public function defineMilestoneForNegotiationOrder(int $providerId, int $orderId, array $data): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $orderId, $data) {
            $order = ServiceOfferOrder::query()
                ->with(['phases.latestEscrow'])
                ->lockForUpdate()
                ->find($orderId);

            if (! $order) {
                throw new NotFoundHttpException(__('services::messages.order.not_found'));
            }

            if ((int) $order->provider_id !== (int) $providerId) {
                throw new HttpException(403, __('services::messages.order.not_owned_by_provider'));
            }

            $status = $order->status instanceof ServiceOfferOrderStatus
                ? $order->status
                : ServiceOfferOrderStatus::tryFrom((string) $order->status);

            // Two modes:
            //   - Negotiation (first define): create a fresh milestone template,
            //     copy its services into phases, attach + flip to PendingPayment.
            //   - PendingPayment (re-define): provider is still negotiating with
            //     the buyer, no phase has been paid yet. Update the existing
            //     milestone template, replace the order's phases. Safe because
            //     there's no escrow to honor — once a phase is PAID, milestone
            //     shape is locked (buyer agreed to that budget).
            //
            // Any other status is refused.
            $isFirstDefine = $status === ServiceOfferOrderStatus::Negotiation;
            $isRedefine    = $status === ServiceOfferOrderStatus::PendingPayment;

            if (! $isFirstDefine && ! $isRedefine) {
                throw new HttpException(
                    422,
                    __('services::messages.milestone_phase.order_not_in_negotiation'),
                );
            }

            if ($isRedefine && $this->orderHasPaidPhase($order)) {
                throw new HttpException(
                    422,
                    __('services::messages.milestone_phase.cannot_redefine_paid'),
                );
            }

            $provider = ServiceProvider::query()->find($providerId);
            if (! $provider) {
                throw new HttpException(403, __('services::messages.order.not_owned_by_provider'));
            }

            if ($isFirstDefine) {
                // Create the Milestone template (lands in the provider's library).
                // activation_status defaults to false in MilestoneService::create
                // — kept off here because the template only exists for this
                // negotiation; provider can toggle it on later if they want to
                // reuse it as a public listing.
                $milestone = $this->milestoneService->create($provider, $data);
            } else {
                // Re-define path: update the existing milestone template the
                // order already points to. MilestoneService::update calls
                // syncForMilestone — adds new lines, updates matching, removes
                // missing — and recalculates the template's aggregates.
                // Defensive fallback: if orderable_id is null or points to a
                // milestone belonging to a different provider, create a fresh
                // template instead of mutating someone else's.
                $existingMilestone = $order->orderable_id
                    ? Milestone::query()->find($order->orderable_id)
                    : null;

                if ($existingMilestone && (int) $existingMilestone->service_provider_id === (int) $provider->id) {
                    $milestone = $this->milestoneService->update($existingMilestone, $data);
                } else {
                    $milestone = $this->milestoneService->create($provider, $data);
                }

                // Wipe stale phases on the order before recreating from the
                // updated template. Safe because we already asserted no phase
                // has an escrow=PAID. Cascade also takes out deliveries /
                // revisions / escrow rows attached to those phases (their FKs
                // cascade-delete on service_offer_order_phases).
                $order->phases()->delete();
            }

            // Copy template lines into per-order phases; sets current_phase_id.
            $this->createPhasesFromTemplate($order, $milestone);

            // Point the order at the template, freeze the milestone header onto
            // it, and flip status to PendingPayment (a no-op on the re-define
            // path, which is already PendingPayment). The template stays
            // editable in the provider's library, so the order must carry its
            // own snapshot of what was agreed; on re-define the phases were just
            // recreated above, so this refreshes the snapshot too. Totals are
            // derived from the phases — only the non-derivable header is stored.
            $order->update([
                'orderable_type'     => OrderableType::MilestoneTemplate->value,
                'orderable_id'       => $milestone->id,
                'milestone_snapshot' => [
                    'title'                     => $milestone->title,
                    'description'               => $milestone->description,
                    'delivery_model'            => $milestone->delivery_model?->value ?? $milestone->delivery_model,
                    'request_modification_days' => $milestone->request_modification_days,
                ],
                'status'             => ServiceOfferOrderStatus::PendingPayment,
            ]);

            return $order->fresh([
                'phases.serviceCategory',
                'currentPhase',
                'referenceServiceOffer',
                'orderable',
                'provider',
                'buyer',
            ]);
        });
    }

    /**
     * True if any phase on this order has its latest escrow in PAID state.
     * Once a phase is paid the milestone shape is locked in — the buyer
     * agreed to that specific budget when they funded the escrow.
     */
    protected function orderHasPaidPhase(ServiceOfferOrder $order): bool
    {
        $phases = $order->relationLoaded('phases')
            ? $order->phases
            : $order->phases()->with('latestEscrow')->get();

        foreach ($phases as $phase) {
            $escrow = $phase->relationLoaded('latestEscrow')
                ? $phase->latestEscrow
                : $phase->latestEscrow()->first();

            if ($escrow && ($escrow->status?->value ?? $escrow->status) === EscrowStatus::Paid->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, ServiceOfferOrderPhase>
     */
    public function createPhasesFromTemplate(ServiceOfferOrder $order, Milestone $template): Collection
    {
        $template->loadMissing(['milestoneServices.serviceCategory']);

        $lines = $template->milestoneServices->sortBy('sort_order')->values();

        if ($lines->isEmpty()) {
            throw new HttpException(422, __('services::messages.milestones.services_required_for_activation'));
        }

        $phases = collect();

        foreach ($lines as $line) {
            $phases->push(ServiceOfferOrderPhase::query()->create([
                'service_offer_order_id' => $order->id,
                'milestone_service_id' => $line->id,
                'service_name' => $line->service_name,
                'description' => $line->description,
                'service_category_id' => $line->service_category_id,
                'duration_days' => $line->duration_days,
                'budget' => $line->budget,
                'revision_window_days' => $line->revision_window_days,
                'sort_order' => $line->sort_order,
                'status' => MilestoneOrderPhaseStatus::PendingPayment,
            ]));
        }

        $first = $phases->first();

        $order->update([
            'current_phase_id' => $first->id,
        ]);

        return $phases;
    }

    /**
     * Mint a Stripe Connect Checkout session for the cursor phase. Returns
     * the order + invoice; controller hands invoice.checkout_url to the
     * frontend for redirect. The phase escrow row and the parent order's
     * PendingPayment → InProgress transition are written by
     * MarkServiceOrderPhasePaidOnInvoiceCompletion when the rail's
     * PaymentSucceeded event fires — not here.
     *
     * Guards (in order): authn, cursor phase match, status=PendingPayment,
     * not already funded, seller Stripe Connect ready. PaymentInitiator's
     * own concurrency guard (lookup by referenceable_type/_id) absorbs
     * duplicate pay POSTs while a Pending invoice already exists.
     *
     * @return array{order: ServiceOfferOrder, invoice: \Modules\Wallet\Models\Invoice}
     */
    public function payPhase(int $buyerId, int $orderId, int $phaseId): array
    {
        return DB::transaction(function () use ($buyerId, $orderId, $phaseId) {
            $order = $this->lockMilestoneOrder($orderId);

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            $phase = $this->resolveCurrentPhase($order, $phaseId);

            if ($phase->status !== MilestoneOrderPhaseStatus::PendingPayment) {
                throw new HttpException(422, __('services::messages.milestone_phase.cannot_pay'));
            }

            if ($phase->isFunded()) {
                throw new HttpException(422, __('services::messages.milestone_phase.already_paid'));
            }

            $order->loadMissing('provider.user');
            SellerStripeAccount::assertReadyForCharges($order->provider->user_id);

            $invoice = $this->payments->initiate(
                kind:          'service_order_phase',
                buyerUserId:   $buyerId,
                sellerUserId:  $order->provider->user_id,
                amount:        (string) $phase->budget,
                currency:      $order->currency,
                referenceable: $phase,
                metadata:      [
                    'service_offer_order_id' => $order->id,
                    'phase_sort_order'       => $phase->sort_order,
                ],
            );

            return [
                'order'   => $this->orderRepository->findById($order->id),
                'invoice' => $invoice,
            ];
        });
    }

    public function startPhase(int $providerId, int $orderId, int $phaseId): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $orderId, $phaseId) {
            $order = $this->lockMilestoneOrder($orderId);

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            $phase = $this->resolveCurrentPhase($order, $phaseId);

            if ($phase->status !== MilestoneOrderPhaseStatus::PendingPayment) {
                throw new HttpException(422, __('services::messages.milestone_phase.cannot_start'));
            }

            if (! $phase->isFunded()) {
                throw new HttpException(422, __('services::messages.milestone_phase.payment_required'));
            }

            $phase->update([
                'status' => MilestoneOrderPhaseStatus::InProgress,
                'started_at' => now(),
                'deadline' => now()->addDays($phase->duration_days),
            ]);

            $order->update([
                'status' => ServiceOfferOrderStatus::InProgress,
                'started_at' => $order->started_at ?? now(),
            ]);

            return $this->orderRepository->findById($order->id);
        });
    }

    public function deliverPhase(
        int $providerId,
        int $orderId,
        int $phaseId,
        ?string $message,
        array $files = []
    ): ServiceOfferOrder {
        return DB::transaction(function () use ($providerId, $orderId, $phaseId, $message, $files) {
            $order = $this->lockMilestoneOrder($orderId);

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            $phase = $this->resolveCurrentPhase($order, $phaseId);

            $allowed = [
                MilestoneOrderPhaseStatus::InProgress,
                MilestoneOrderPhaseStatus::InRevision,
            ];

            if (! in_array($phase->status, $allowed, true)) {
                throw new HttpException(422, __('services::messages.milestone_phase.cannot_deliver'));
            }

            if ($phase->status === MilestoneOrderPhaseStatus::InRevision) {
                $this->assertAcceptedRevisionForPhase($phase);
            }

            $lastVersion = (int) $phase->deliveries()->max('version');
            $version = $lastVersion + 1;

            $attachments = [];
            foreach ($files as $file) {
                $attachments[] = FileManager::upload('orders/deliveries', $file);
            }

            $phase->deliveries()->create([
                'service_offer_order_id' => $order->id,
                'version' => $version,
                'message' => $message,
                'attachments' => $attachments,
                'submitted_at' => now(),
            ]);

            if ($phase->status === MilestoneOrderPhaseStatus::InRevision) {
                $phase->revisions()
                    ->where('status', ServiceOfferOrderRevisionStatus::Pending)
                    ->update([
                        'status' => ServiceOfferOrderRevisionStatus::Accepted,
                        'responded_at' => now(),
                    ]);
            }

            $phase->update([
                'status' => MilestoneOrderPhaseStatus::Delivered,
                'delivered_at' => now(),
            ]);

            $order->update(['status' => ServiceOfferOrderStatus::Delivered]);

            return $this->orderRepository->findById($order->id);
        });
    }

    public function acceptPhase(int $buyerId, int $orderId, int $phaseId): ServiceOfferOrder
    {
        return DB::transaction(function () use ($buyerId, $orderId, $phaseId) {
            $order = $this->lockMilestoneOrder($orderId);

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            $phase = $this->resolveCurrentPhase($order, $phaseId);

            if ($phase->status !== MilestoneOrderPhaseStatus::Delivered) {
                throw new HttpException(422, __('services::messages.milestone_phase.cannot_accept'));
            }

            if ($phase->revisions()->where('status', ServiceOfferOrderRevisionStatus::Pending)->exists()) {
                throw new HttpException(422, __('services::messages.milestone_phase.pending_revision'));
            }

            $escrow = $phase->latestEscrow;
            if ($escrow) {
                $escrow->update(['status' => EscrowStatus::Released]);
            }

            $phase->update([
                'status' => MilestoneOrderPhaseStatus::Completed,
                'completed_at' => now(),
            ]);

            $next = $order->phases()
                ->where('sort_order', '>', $phase->sort_order)
                ->orderBy('sort_order')
                ->first();

            if ($next) {
                $order->update([
                    'current_phase_id' => $next->id,
                    'status' => ServiceOfferOrderStatus::InProgress,
                ]);
            } else {
                $order->update([
                    'current_phase_id' => null,
                    'status' => ServiceOfferOrderStatus::Completed,
                ]);
            }

            return $this->orderRepository->findById($order->id);
        });
    }

    public function requestPhaseRevision(int $buyerId, int $orderId, int $phaseId, array $data): ServiceOfferOrder
    {
        return DB::transaction(function () use ($buyerId, $orderId, $phaseId, $data) {
            $order = $this->lockMilestoneOrder($orderId);

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            $phase = $this->resolveCurrentPhase($order, $phaseId);

            if ($phase->status !== MilestoneOrderPhaseStatus::Delivered) {
                throw new HttpException(422, __('services::messages.milestone_phase.cannot_request_revision'));
            }

            if ($phase->allowedRevisions() <= 0) {
                throw new HttpException(422, __('services::messages.milestone_phase.no_revision_allowed'));
            }

            if ($phase->revisionsUsed() >= $phase->allowedRevisions()) {
                throw new HttpException(422, __('services::messages.milestone_phase.no_revision_credits'));
            }

            if ($phase->revisions()->where('status', ServiceOfferOrderRevisionStatus::Pending)->exists()) {
                throw new HttpException(422, __('services::messages.milestone_phase.revision_in_progress'));
            }

            if ($phase->delivered_at && $phase->revision_window_days > 0) {
                $deadline = $phase->delivered_at->copy()->addDays($phase->revision_window_days);
                if (now()->isAfter($deadline)) {
                    throw new HttpException(422, __('services::messages.milestone_phase.revision_window_expired'));
                }
            }

            $deliveryVersion = (int) $data['delivery_version'];
            if (! $phase->deliveries()->where('version', $deliveryVersion)->exists()) {
                throw new HttpException(422, __('services::messages.milestone_phase.invalid_delivery_version'));
            }

            $attachments = [];
            foreach ($data['attachments'] ?? [] as $file) {
                $attachments[] = FileManager::upload('orders/revisions', $file);
            }

            $phase->revisions()->create([
                'service_offer_order_id' => $order->id,
                'delivery_version' => $deliveryVersion,
                'notes' => $data['notes'],
                'request_type' => $data['request_type'] ?? 'STANDARD',
                'attachments' => $attachments !== [] ? $attachments : null,
                'status' => ServiceOfferOrderRevisionStatus::Pending,
                'requested_at' => now(),
            ]);

            $order->update(['status' => ServiceOfferOrderStatus::RevisionRequested]);

            return $this->orderRepository->findById($order->id);
        });
    }

    public function acceptPhaseRevision(int $providerId, int $revisionId): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $revisionId) {
            $revision = ServiceOfferOrderRevision::query()
                ->whereKey($revisionId)
                ->lockForUpdate()
                ->firstOrFail();

            $order = $this->lockMilestoneOrder($revision->service_offer_order_id);

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if (! $revision->service_offer_order_phase_id) {
                throw new HttpException(422, __('services::messages.milestone_phase.not_a_phase_revision'));
            }

            if ($revision->status !== ServiceOfferOrderRevisionStatus::Pending) {
                throw new HttpException(422, __('services::messages.order.revision_accepted_successfully'));
            }

            $revision->update([
                'status' => ServiceOfferOrderRevisionStatus::Accepted,
                'responded_at' => now(),
            ]);

            $revision->phase?->update(['status' => MilestoneOrderPhaseStatus::InRevision]);
            $order->update(['status' => ServiceOfferOrderStatus::InRevision]);

            return $this->orderRepository->findById($order->id);
        });
    }

    public function rejectPhaseRevision(int $providerId, int $revisionId, string $reason): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $revisionId, $reason) {
            $revision = ServiceOfferOrderRevision::query()
                ->whereKey($revisionId)
                ->lockForUpdate()
                ->firstOrFail();

            $order = $this->lockMilestoneOrder($revision->service_offer_order_id);

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if (! $revision->service_offer_order_phase_id) {
                throw new HttpException(422, __('services::messages.milestone_phase.not_a_phase_revision'));
            }

            if ($revision->status !== ServiceOfferOrderRevisionStatus::Pending) {
                throw new HttpException(422, 'Revision already processed');
            }

            $revision->update([
                'status' => ServiceOfferOrderRevisionStatus::Rejected,
                'rejection_reason' => $reason,
                'responded_at' => now(),
            ]);

            $revision->phase?->update(['status' => MilestoneOrderPhaseStatus::Delivered]);
            $order->update(['status' => ServiceOfferOrderStatus::Delivered]);

            return $this->orderRepository->findById($order->id);
        });
    }

    protected function lockMilestoneOrder(int $orderId): ServiceOfferOrder
    {
        $order = ServiceOfferOrder::query()
            ->whereKey($orderId)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $order->isMilestoneOrder()) {
            throw new HttpException(422, __('services::messages.milestone_phase.not_milestone_order'));
        }

        $order->load(['phases.latestEscrow', 'phases.serviceCategory', 'currentPhase']);

        return $order;
    }

    protected function resolveCurrentPhase(ServiceOfferOrder $order, int $phaseId): ServiceOfferOrderPhase
    {
        if ((int) $order->current_phase_id !== $phaseId) {
            throw new HttpException(422, __('services::messages.milestone_phase.not_current'));
        }

        $phase = $order->phases->firstWhere('id', $phaseId);

        if (! $phase) {
            throw new HttpException(404, __('services::messages.milestone_phase.not_found'));
        }

        return $phase;
    }

    protected function assertAcceptedRevisionForPhase(ServiceOfferOrderPhase $phase): void
    {
        $accepted = $phase->revisions()
            ->where('status', ServiceOfferOrderRevisionStatus::Accepted)
            ->exists();

        if (! $accepted) {
            throw new HttpException(422, __('services::messages.milestone_phase.no_accepted_revision'));
        }
    }
}
