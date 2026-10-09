<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Services\Enums\DeliveryTimeUnit;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Enums\ServiceOfferOrderExtensionStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Services\Models\ServiceOfferOrderExtension;
use Modules\Services\Models\ServiceOfferOrderExtensionReason;
use Modules\Services\Models\ServiceOfferOrderRejectionReason;
use Modules\Services\Models\ServiceOfferPlan;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceProviderRepositoryInterface;
use Modules\Services\Utilities\ServiceFormatHelper;
use Modules\Wallet\Contracts\PaymentInitiatorInterface;
use Modules\Wallet\Enums\EscrowStatus;
use Modules\Wallet\Enums\RefundReason;
use Modules\Wallet\Models\Escrow;
use Modules\Wallet\Models\SellerStripeAccount;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceOfferOrderService
{
    public function __construct(
        protected ServiceOfferOrderRepositoryInterface $orderRepository,
        protected ServiceOfferRepositoryInterface $offerRepository,
        protected ServiceProviderRepositoryInterface $providerRepository,
        protected ServiceOfferOrderRevisionService $revisionService,
        protected PaymentInitiatorInterface $payments,
    ) {}

    /**
     * Create a service order, dispatching by orderable_type.
     *
     * Single-offer: mints a Stripe Connect Checkout session via PaymentInitiator.
     * Returns ['order' => ..., 'invoice' => Invoice with checkout_url]. Order
     * stays PendingPayment until checkout.session.completed → PaymentSucceeded
     * → MarkServiceOrderPaidOnInvoiceCompletion listener.
     *
     * Milestone-template: inserts a negotiation order row only (no phases).
     * Returns ['order' => ..., 'invoice' => null].
     *
     * Order + invoice creation share one DB transaction. If PaymentInitiator
     * throws (Stripe reject, Connect account ineligible), order rolls back too —
     * no orphaned PendingPayment rows.
     */
    public function createOrder(int $buyerId, array $data): array
    {
        $orderableType = OrderableType::tryFromPayload((string) ($data['orderable_type'] ?? ''));

        if (! $orderableType) {
            throw new HttpException(422, __('services::messages.order.invalid_orderable_type'));
        }

        return match ($orderableType) {
            OrderableType::ServiceOffer => $this->createServiceOfferOrder($buyerId, $data),
            OrderableType::MilestoneTemplate => $this->createMilestonePackageOrder($buyerId, $data),
        };
    }

    /**
     * @return array{order: ServiceOfferOrder, invoice: \Modules\Wallet\Models\Invoice}
     */
    protected function createServiceOfferOrder(int $buyerId, array $data): array
    {
        return DB::transaction(function () use ($buyerId, $data) {
            $offer = $this->offerRepository->findById((int) $data['orderable_id']);

            if (! $offer) {
                throw new HttpException(404, __('services::messages.service_offers.not_found'));
            }

            if (! $offer->is_active) {
                throw new HttpException(422, __('services::messages.order.offer_paused'));
            }

            $provider = $offer->serviceProvider;

            if (! $provider) {
                throw new HttpException(422, __('services::messages.order.provider_not_found'));
            }

            $this->assertBuyerMayOrder($provider->id, $buyerId, $provider);

            // Block order creation if the seller hasn't completed Stripe Connect
            // onboarding. Without this, PaymentInitiator below would throw
            // NoSupportedRailException — less actionable for the buyer.
            SellerStripeAccount::assertReadyForCharges($provider->user_id);

            $pricing = $this->resolveOrderPricing(
                $offer,
                isset($data['plan_id']) ? (int) $data['plan_id'] : null
            );

            $requirements = $this->buildRequirements($data, $pricing['plan_id']);

            $order = $this->orderRepository->create([
                'order_number' => $this->generateOrderNumber(),
                'buyer_id' => $buyerId,
                'provider_id' => $provider->id,
                'orderable_type' => OrderableType::ServiceOffer->value,
                'orderable_id' => $offer->id,
                'title' => $offer->title,
                'price' => $pricing['price'],
                'currency' => $pricing['currency'],
                'delivery_time_minutes' => $pricing['delivery_time_minutes'],
                'requirements' => $requirements,
                'status' => ServiceOfferOrderStatus::PendingPayment,
            ]);

            // Mint the Invoice + Stripe Connect Checkout Session. Order is
            // passed as the polymorphic referenceable so the listener can
            // find it when the payment webhook lands. PaymentInitiator's
            // own concurrency guard (lookup by referenceable_type/_id)
            // handles accidental duplicate POSTs.
            $invoice = $this->payments->initiate(
                kind: 'service_order',
                buyerUserId: $buyerId,
                sellerUserId: $provider->user_id,
                amount: (string) $pricing['price'],
                currency: $pricing['currency'],
                referenceable: $order,
                metadata: [
                    'orderable_type' => OrderableType::ServiceOffer->value,
                    'orderable_id'   => $offer->id,
                    'plan_id'        => $pricing['plan_id'],
                ],
            );

            return [
                'order'   => $this->orderRepository->findById($order->id),
                'invoice' => $invoice,
            ];
        });
    }

    /**
     * @return array{order: ServiceOfferOrder, invoice: null}
     */
    protected function createMilestonePackageOrder(int $buyerId, array $data): array
    {
        return DB::transaction(function () use ($buyerId, $data) {
            $offer = $this->offerRepository->findById((int) $data['service_offer_id']);

            if (! $offer) {
                throw new HttpException(404, __('services::messages.service_offers.not_found'));
            }

            if (! $offer->is_active) {
                throw new HttpException(422, __('services::messages.order.offer_paused'));
            }

            if (! $offer->milestone_available) {
                throw new HttpException(422, __('services::messages.order.milestones_not_available'));
            }

            $provider = $offer->serviceProvider;

            if (! $provider) {
                throw new HttpException(422, __('services::messages.order.provider_not_found'));
            }

            $this->assertBuyerMayOrder($provider->id, $buyerId, $provider);

            $pricing = $this->resolveOrderPricing(
                $offer,
                isset($data['plan_id']) ? (int) $data['plan_id'] : null
            );

            $requirements = $this->buildRequirements($data, $pricing['plan_id'], OrderableType::MilestoneTemplate);

            $order = $this->orderRepository->create([
                'order_number' => $this->generateOrderNumber(),
                'buyer_id' => $buyerId,
                'provider_id' => $provider->id,
                'reference_service_offer_id' => $offer->id,
                'orderable_type' => null,
                'orderable_id' => null,
                'title' => $offer->title,
                'price' => $pricing['price'],
                'currency' => $pricing['currency'],
                'delivery_time_minutes' => $pricing['delivery_time_minutes'],
                'requirements' => $requirements,
                'status' => ServiceOfferOrderStatus::Negotiation,
            ]);

            return [
                'order'   => $this->orderRepository->findById($order->id),
                'invoice' => null,
            ];
        });
    }

    protected function assertBuyerMayOrder(int $providerId, int $buyerId, $provider): void
    {
        if (! $provider->user?->is_active) {
            throw new HttpException(422, __('services::messages.order.provider_deactivated'));
        }

        if ($this->providerRepository->isCustomerBlocked($providerId, $buyerId)) {
            throw new HttpException(403, __('services::messages.order.buyer_blocked'));
        }
    }

    /**
     * @return array{price: string|float, currency: string, delivery_time_minutes: ?int, plan_id: ?int}
     */
    protected function resolveOrderPricing(ServiceOffer $offer, ?int $planId): array
    {
        $offer->loadMissing('plans');

        if ($offer->plans->isNotEmpty()) {
            if ($planId !== null) {
                $plan = $offer->plans->firstWhere('id', $planId);

                if (! $plan) {
                    throw new HttpException(422, __('services::messages.order.invalid_plan'));
                }

                return $this->pricingFromPlan($plan);
            }

            $plan = $offer->plans->sortBy('price')->first();

            return $this->pricingFromPlan($plan);
        }

        if ($planId !== null) {
            throw new HttpException(422, __('services::messages.order.invalid_plan'));
        }

        if ($offer->base_price === null) {
            throw new HttpException(422, __('services::messages.order.pricing_not_configured'));
        }

        $deliveryMinutes = null;
        if ($offer->base_delivery_time_value !== null && $offer->base_delivery_time_unit !== null) {
            $deliveryMinutes = $this->convertToMinutes(
                (int) $offer->base_delivery_time_value,
                $offer->base_delivery_time_unit
            );
        }

        return [
            'price' => $offer->base_price,
            'currency' => (string) ($offer->base_currency ?? config('services.default_currency', 'USD')),
            'delivery_time_minutes' => $deliveryMinutes,
            'plan_id' => null,
        ];
    }

    /**
     * @return array{price: string|float, currency: string, delivery_time_minutes: int, plan_id: int}
     */
    protected function pricingFromPlan(ServiceOfferPlan $plan): array
    {
        return [
            'price' => $plan->price,
            'currency' => $plan->currency,
            'delivery_time_minutes' => $this->convertToMinutes(
                (int) $plan->delivery_time_value,
                $plan->delivery_time_unit
            ),
            'plan_id' => $plan->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildRequirements(
        array $data,
        ?int $planId = null,
        ?OrderableType $orderType = OrderableType::ServiceOffer
    ): array {
        $attachments = [];

        if (! empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                $attachments[] = FileManager::upload('orders/attachments', $file);
            }
        }

        $requirements = [
            'notes' => $data['notes'] ?? null,
            'attachments' => $attachments,
            'order_type' => $orderType->value,
        ];

        if ($planId !== null) {
            $requirements['plan_id'] = $planId;
        }

        return $requirements;
    }

    protected function generateOrderNumber(): string
    {
        $year = (int) now()->format('Y');

        $lastOrder = $this->orderRepository->getLastOrderOfYear((string) $year);
        $next = ServiceFormatHelper::nextServiceOfferOrderSequence($lastOrder?->order_number);

        return ServiceFormatHelper::formatServiceOfferOrderNumber($year, $next);
    }

    private function convertToMinutes(int $value, DeliveryTimeUnit $unit): int
    {
        return match ($unit) {
            DeliveryTimeUnit::Minutes => $value,
            DeliveryTimeUnit::Hours => $value * 60,
            DeliveryTimeUnit::Days => $value * 1440,
        };
    }

    public function getUserOrders(int $buyerId, ?ServiceOfferOrderStatus $status = null)
    {
        return $this->orderRepository->getOrdersByBuyer($buyerId, $status);
    }

    public function getProviderOrders(int $providerId, ?ServiceOfferOrderStatus $status = null)
    {
        return $this->orderRepository->getOrdersByProvider($providerId, $status);
    }

    /**
     * Loads an order for either buyer or provider display, asserting the
     * caller is authorized to see it.
     *
     * @param  int    $callerId  Buyer's user id OR provider's service_provider id,
     *                           depending on $type. The Provider controller
     *                           passes auth()->user()->serviceProvider->id;
     *                           the User controller passes auth()->id().
     *                           Don't mix them up.
     * @param  int    $orderId   The order to load.
     * @param  string $type      'buyer' or 'provider'.
     */
    public function getOrderDetails(int $callerId, int $orderId, string $type = 'buyer')
    {
        $order = $this->orderRepository->findById($orderId);

        $isAuthorized = match ($type) {
            'buyer'    => $order->buyer_id === $callerId,
            // The order's provider_id is the service_providers PK (matches the
            // FK on service_offer_orders). Every other provider-side method
            // here compares against $order->provider_id directly — historically
            // this branch compared against $order->provider->user_id, which is
            // a different table's PK; matched only by coincidence and 403'd
            // for any provider whose service_provider.id != users.id. Fixed.
            'provider' => $order->provider_id === $callerId,
            default    => false,
        };

        if (! $isAuthorized) {
            throw new HttpException(403, 'Unauthorized');
        }

        return $order;
    }

    public function getServiceOrders(int $serviceOfferId, int $providerId, ?ServiceOfferOrderStatus $status = null)
    {
        $offer = $this->offerRepository->findById($serviceOfferId);

        if ($offer->service_provider_id !== $providerId) {
            throw new HttpException(403, 'Unauthorized');
        }

        return $this->orderRepository->getOrdersByService($serviceOfferId, $status);
    }

    public function startOrder(int $providerId, int $orderId)
    {
        return DB::transaction(function () use ($providerId, $orderId) {
            $order = $this->orderRepository->findById($orderId);

            if ($order->isMilestoneOrder()) {
                throw new HttpException(422, __('services::messages.milestone_phase.use_phase_endpoints'));
            }

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->status !== ServiceOfferOrderStatus::Pending) {
                throw new HttpException(422, 'Order cannot be started');
            }

            $escrow = $order->latestEscrow;

            if (! $escrow || $escrow->status?->value !== 'PAID') {
                throw new HttpException(422, 'Order is not funded yet');
            }

            $deliveryMinutes = $order->effectiveDeliveryMinutes();

            return $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::InProgress,
                'started_at' => now(),
                'deadline' => now()->addMinutes($deliveryMinutes),
            ], $order);
        });
    }

    public function rejectOrder(
        int $providerId,
        int $orderId,
        int $reasonId,
        ?string $notes
    ) {
        return DB::transaction(function () use ($providerId, $orderId, $reasonId, $notes) {
            $order = $this->orderRepository->findById($orderId);

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->status !== ServiceOfferOrderStatus::Pending) {
                throw new HttpException(422, 'Order cannot be rejected at this stage');
            }

            $reason = ServiceOfferOrderRejectionReason::where('id', $reasonId)
                ->where('is_active', true)
                ->first();

            if (! $reason) {
                throw new HttpException(422, 'Invalid rejection reason');
            }

            $escrow = $order->latestEscrow;

            if ($escrow) {
                $escrow->update([
                    'status' => 'FAILED',
                ]);
            }

            return $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::Rejected,
                'rejection_reason_id' => $reasonId,
                'rejection_notes' => $notes,
            ], $order);
        });
    }

    public function deliverOrder(
        int $providerId,
        int $orderId,
        ?string $message,
        array $files = []
    ) {
        return DB::transaction(function () use ($providerId, $orderId, $message, $files) {
            $order = ServiceOfferOrder::query()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->isMilestoneOrder()) {
                throw new HttpException(422, __('services::messages.milestone_phase.use_phase_endpoints'));
            }

            $allowedStatuses = [
                ServiceOfferOrderStatus::InProgress,
                ServiceOfferOrderStatus::InRevision,
            ];

            if (! in_array($order->status, $allowedStatuses, true)) {
                throw new HttpException(422, 'Order cannot be delivered at this stage');
            }

            $this->revisionService->assertPendingRevisionExistsForDelivery($order);

            $lastVersion = $order->deliveries()
                ->lockForUpdate()
                ->max('version');

            $version = ($lastVersion ?? 0) + 1;

            $attachments = [];

            foreach ($files as $file) {
                $attachments[] = FileManager::upload('orders/deliveries', $file);
            }

            $order->deliveries()->create([
                'version' => $version,
                'message' => $message,
                'attachments' => $attachments,
                'submitted_at' => now(),
            ]);

            $this->revisionService->markPendingRevisionsAcceptedAfterDelivery($order);

            return $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::Delivered,
            ], $order);
        });
    }

    /**
     * Buyer accepts the delivery on a single-offer order. The non-milestone
     * counterpart of MilestoneOrderPhaseService::acceptPhase — that one
     * existed; this one didn't, leaving single-offer orders without any way
     * to reach a terminal state. Result: escrow rows stuck at PAID, orders
     * stuck at Delivered, sellers never marked as paid in our internal
     * ledger.
     *
     * Lifecycle:
     *   Delivered (single-offer) -> Completed
     *   Escrow PAID -> Released
     *
     * Guards:
     *   - Buyer must own the order.
     *   - Status must be Delivered (the only state that allows acceptance).
     *   - Refuses to accept while a pending revision is unresolved — the
     *     buyer must either reject the revision or wait for redelivery
     *     before formally accepting.
     *   - Refuses on milestone orders — those use acceptPhase per phase.
     *
     * Atomic transaction wraps the escrow update + status flip + timestamp.
     * If either side fails the whole thing rolls back so we don't end up
     * with a Completed order whose escrow is still PAID (or vice versa).
     */
    public function acceptOrder(int $buyerId, int $orderId)
    {
        return DB::transaction(function () use ($buyerId, $orderId) {
            $order = ServiceOfferOrder::query()
                ->whereKey($orderId)
                ->with(['latestEscrow', 'revisions'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->isMilestoneOrder()) {
                throw new HttpException(422, __('services::messages.milestone_phase.use_phase_endpoints'));
            }

            if ($order->status !== ServiceOfferOrderStatus::Delivered) {
                throw new HttpException(422, __('services::messages.order.cannot_accept'));
            }

            // Pending revision = the buyer has an open revision request that
            // the provider hasn't accepted/rejected. Formally accepting the
            // order would orphan that revision; refuse and let the buyer
            // resolve the revision flow first.
            $hasPendingRevision = $order->revisions
                ->where('status', \Modules\Services\Enums\ServiceOfferOrderRevisionStatus::Pending)
                ->isNotEmpty();
            if ($hasPendingRevision) {
                throw new HttpException(422, __('services::messages.order.cannot_accept_pending_revision'));
            }

            $escrow = $order->latestEscrow;
            if ($escrow) {
                $escrow->update(['status' => EscrowStatus::Released]);
            }

            // Order has no completed_at column; relying on updated_at +
            // status='COMPLETED' is sufficient. Mirrors how milestone
            // phases track completion (per-phase has completed_at, the
            // parent order doesn't).
            $order->update([
                'status' => ServiceOfferOrderStatus::Completed,
            ]);

            return $this->orderRepository->findById($order->id);
        });
    }

    public function cancelOrder(int $buyerId, int $orderId)
    {
        return DB::transaction(function () use ($buyerId, $orderId) {
            $order = $this->orderRepository->findById($orderId);

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->status === ServiceOfferOrderStatus::Cancelled) {
                throw new HttpException(422, 'Order already cancelled');
            }

            if (in_array($order->status, [
                ServiceOfferOrderStatus::InProgress,
                ServiceOfferOrderStatus::Delivered,
                ServiceOfferOrderStatus::Completed,
                ServiceOfferOrderStatus::RevisionRequested,
            ], true)) {
                throw new HttpException(422, 'Order cannot be cancelled at this stage');
            }

            return $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::Cancelled,
            ], $order);
        });
    }

    public function cancelOrderByProvider(int $providerId, int $orderId): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $orderId) {
            $order = ServiceOfferOrder::query()
                ->with(['latestEscrow', 'deliveries'])
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->status === ServiceOfferOrderStatus::Cancelled) {
                throw new HttpException(422, 'Order already cancelled');
            }

            if ($order->status === ServiceOfferOrderStatus::Disputed) {
                throw new HttpException(422, 'Cannot cancel order during dispute');
            }

            $escrow = $order->latestEscrow;

            if ($order->status === ServiceOfferOrderStatus::PendingPayment) {
                return $this->orderRepository->update([
                    'status' => ServiceOfferOrderStatus::Cancelled,
                ], $order);
            }

            if ($order->status === ServiceOfferOrderStatus::Pending) {
                $this->handleFullRefund($escrow);

                return $this->orderRepository->update([
                    'status' => ServiceOfferOrderStatus::Cancelled,
                ], $order);
            }

            if (in_array($order->status, [
                ServiceOfferOrderStatus::InProgress,
                ServiceOfferOrderStatus::InRevision,
            ], true)) {
                $this->handlePolicyRefund($order, $escrow);

                return $this->orderRepository->update([
                    'status' => ServiceOfferOrderStatus::Cancelled,
                ], $order);
            }

            throw new HttpException(422, 'Order cannot be cancelled at this stage');
        });
    }

    /**
     * Auto-issue a full refund through the original rail. Called from
     * cancelOrderByProvider() when an order is cancelled after payment but
     * before work has started (status=Pending).
     *
     * Uses the escrow's direct invoice FK to find the funding Invoice, then
     * delegates to PaymentInitiator::refund which reverses the rail-side
     * charge + transitions the invoice to REFUNDED. For Stripe Connect this
     * reverses both the seller payout AND the platform application fee.
     *
     * If the escrow has no linked invoice, it's pre-payment-integration data
     * (gateway='manual', no matching invoice). Mark Refunded for status
     * consistency and log — admin reconciles manually.
     *
     * Runs inside the cancelOrderByProvider() DB transaction. If the rail
     * rejects (Stripe API error), the transaction rolls back; the order
     * stays in its pre-cancel state.
     */
    protected function handleFullRefund(?Escrow $escrow): void
    {
        if (! $escrow) {
            return;
        }

        if (! $escrow->invoice) {
            Log::warning('handleFullRefund: escrow has no linked invoice — pre-integration data; marking Refunded for status only', [
                'escrow_id'      => $escrow->id,
                'gateway'        => $escrow->gateway,
                'transaction_id' => $escrow->transaction_id,
            ]);
            $escrow->update(['status' => EscrowStatus::Refunded]);
            return;
        }

        $this->payments->refund(
            invoice: $escrow->invoice,
            amount: (string) $escrow->invoice->amount,
            reason: RefundReason::RequestedByCustomer->value,
            actorType: 'user',
            actorId: $escrow->invoice->user_id,
        );

        $escrow->update(['status' => EscrowStatus::Refunded]);
    }

    /**
     * Block self-cancellation of an order whose work has already begun
     * (status InProgress or InRevision). Partial / policy refunds in this
     * state require business judgment — that's an admin decision, not a
     * buyer's. v1 routes the buyer to support or to opening a dispute.
     *
     * Future: an admin-only endpoint calls rail->refund(invoice, $partialAmount,
     * $reason) directly with whatever amount the admin decides. Separate PR
     * scoped to admin tooling.
     */
    protected function handlePolicyRefund(ServiceOfferOrder $order, ?Escrow $escrow): void
    {
        throw new HttpException(
            422,
            'Cancellation of an in-progress order requires admin approval. Please open a dispute or contact support to request a refund.',
        );
    }

    public function changeStatus(int $providerId, int $orderId, string $newStatus)
    {
        $order = $this->orderRepository->findById($orderId);

        if ($order->provider_id !== $providerId) {
            throw new HttpException(403, 'Unauthorized');
        }

        $oldStatus = $order->status;

        if ($oldStatus->value === $newStatus) {
            return $order;
        }

        $this->validateStatusTransition($oldStatus, $newStatus);

        return DB::transaction(function () use ($order, $newStatus, $oldStatus) {
            $order->update([
                'status' => $newStatus,
            ]);

            // PendingPayment → Pending transitions are now exclusively driven
            // by the MarkServiceOrderPaidOnInvoiceCompletion listener (fired by
            // Wallet's PaymentSucceeded event when the buyer's Stripe Connect
            // Checkout completes). The old createPaidEscrow shortcut here used
            // to fabricate a fake escrow row with gateway='manual' — removed.
            // If changeStatus is invoked with this transition directly, the
            // order will move to Pending but no escrow will exist, and
            // startOrder() will refuse it — surfacing the bug rather than
            // papering over it.

            return $order->fresh(['latestEscrow']);
        });
    }

    protected function validateStatusTransition($oldStatus, string $newStatus): void
    {
        $allowedTransitions = [
            ServiceOfferOrderStatus::PendingPayment->value => [
                ServiceOfferOrderStatus::Pending->value,
                ServiceOfferOrderStatus::Cancelled->value,
            ],
            ServiceOfferOrderStatus::Pending->value => [
                ServiceOfferOrderStatus::InProgress->value,
                ServiceOfferOrderStatus::Rejected->value,
            ],
        ];

        if (! in_array($newStatus, $allowedTransitions[$oldStatus->value] ?? [], true)) {
            throw new \Exception("Invalid status transition from {$oldStatus->value} to {$newStatus}");
        }
    }

    public function requestExtension(
        int $providerId,
        int $orderId,
        int $days,
        int $reasonId,
        ?string $description = null
    ) {
        return DB::transaction(function () use ($providerId, $orderId, $days, $reasonId, $description) {
            $order = $this->orderRepository->findById($orderId);

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->status !== ServiceOfferOrderStatus::InProgress) {
                throw new HttpException(422, 'Extension can only be requested during progress');
            }

            $hasPending = $order->extensions()
                ->where('status', 'PENDING')
                ->exists();

            if ($hasPending) {
                throw new HttpException(422, 'There is already a pending extension request');
            }

            if ($days <= 0 || $days > 30) {
                throw new HttpException(422, 'Invalid extension duration');
            }

            $reason = ServiceOfferOrderExtensionReason::query()
                ->where('id', $reasonId)
                ->where('is_active', true)
                ->first();

            if (! $reason) {
                throw new HttpException(422, 'Invalid or inactive extension reason');
            }

            $order->extensions()->create([
                'reason_id' => $reasonId,
                'requested_days' => $days,
                'description' => $description,
                'status' => ServiceOfferOrderExtensionStatus::Pending,
            ]);

            return $order->fresh(['extensions.reason']);
        });
    }

    public function approveExtension(int $buyerId, int $extensionId)
    {
        return DB::transaction(function () use ($buyerId, $extensionId) {
            $extension = ServiceOfferOrderExtension::query()
                ->with('order.extensions.reason')
                ->lockForUpdate()
                ->findOrFail($extensionId);

            $order = $extension->order;

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($extension->status !== ServiceOfferOrderExtensionStatus::Pending) {
                throw new HttpException(422, 'Extension already processed');
            }

            $extension->update([
                'status' => ServiceOfferOrderExtensionStatus::Approved,
                'responded_at' => now(),
            ]);

            if ($order->deadline) {
                $order->deadline = $order->deadline->addDays($extension->requested_days);
            } else {
                $order->deadline = now()->addDays($extension->requested_days);
            }

            $order->save();

            return $order->fresh(['extensions.reason']);
        });
    }

    public function rejectExtension(int $buyerId, int $extensionId)
    {
        return DB::transaction(function () use ($buyerId, $extensionId) {
            $extension = ServiceOfferOrderExtension::query()
                ->with('order.extensions.reason')
                ->lockForUpdate()
                ->findOrFail($extensionId);

            $order = $extension->order;

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($extension->status !== ServiceOfferOrderExtensionStatus::Pending) {
                throw new HttpException(422, 'Extension already processed');
            }

            $extension->update([
                'status' => ServiceOfferOrderExtensionStatus::Rejected,
                'responded_at' => now(),
            ]);

            return $order->fresh(['extensions.reason']);
        });
    }
}
