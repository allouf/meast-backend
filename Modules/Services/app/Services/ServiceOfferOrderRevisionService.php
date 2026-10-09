<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Services\Models\ServiceOfferOrderRevision;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceOfferOrderRevisionService
{
    public function __construct(
        protected ServiceOfferOrderRepositoryInterface $orderRepository,
    ) {}

    public function requestRevision(int $buyerId, int $orderId, array $data): ServiceOfferOrder
    {
        return DB::transaction(function () use ($buyerId, $orderId, $data) {
            $order = ServiceOfferOrder::query()
                ->with(['plan', 'deliveries'])
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->buyer_id !== $buyerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($order->isMilestoneOrder()) {
                throw new HttpException(422, __('services::messages.milestone_phase.use_phase_revision_endpoint'));
            }

            if ($order->status !== ServiceOfferOrderStatus::Delivered) {
                throw new HttpException(422, 'Revisions can only be requested after a delivery');
            }

            $allowed = $order->allowedRevisions();

            $used = $order->revisions()
                ->where('status', '!=', ServiceOfferOrderRevisionStatus::Rejected->value)
                ->count();

            if ($allowed <= 0 || $used >= $allowed) {
                throw new HttpException(422, 'No revision credits remaining for this order');
            }

            if ($order->revisions()->where('status', ServiceOfferOrderRevisionStatus::Pending->value)->exists()) {
                throw new HttpException(422, 'A revision is already in progress for this order');
            }

            $deliveryVersion = (int) $data['delivery_version'];

            if (! $order->deliveries()->where('version', $deliveryVersion)->exists()) {
                throw new HttpException(422, 'Invalid delivery version for this order');
            }

            $attachments = [];

            foreach ($data['attachments'] ?? [] as $file) {
                $attachments[] = FileManager::upload('orders/revisions', $file);
            }

            $order->revisions()->create([
                'delivery_version' => $deliveryVersion,
                'notes' => $data['notes'],
                'request_type' => $data['request_type'],
                'attachments' => $attachments !== [] ? $attachments : null,
                'status' => ServiceOfferOrderRevisionStatus::Pending,
                'requested_at' => now(),
            ]);

            $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::RevisionRequested,
            ], $order);

            return $this->orderRepository->findById($orderId);
        });
    }

    public function rejectRevisionRequest(int $providerId, int $revisionId, string $reason): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $revisionId, $reason) {
            $revision = ServiceOfferOrderRevision::query()
                ->whereKey($revisionId)
                ->lockForUpdate()
                ->firstOrFail();

            $order = ServiceOfferOrder::query()
                ->whereKey($revision->service_offer_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            if ($revision->status !== ServiceOfferOrderRevisionStatus::Pending) {
                throw new HttpException(422, 'This revision request is no longer pending');
            }

            if ($order->status !== ServiceOfferOrderStatus::RevisionRequested) {
                throw new HttpException(422, 'Order is not awaiting a revision response');
            }

            $revision->update([
                'status' => ServiceOfferOrderRevisionStatus::Rejected,
                'rejection_reason' => $reason,
                'responded_at' => now(),
            ]);

            $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::Delivered,
            ], $order);

            return $this->orderRepository->findById($order->id);
        });
    }

    /**
     * @return array{order_id: int, revisions_allowed: int, revisions_used: int, revisions_remaining: int, revisions: \Illuminate\Database\Eloquent\Collection}
     */
    public function getOrderRevisions(int $userId, int $orderId, string $type = 'buyer'): array
    {
        $order = $this->orderRepository->findById($orderId);

        $isAuthorized = match ($type) {
            'buyer' => $order->buyer_id === $userId,
            'provider' => $order->provider_id === $userId,
            default => false,
        };

        if (! $isAuthorized) {
            throw new HttpException(403, 'Unauthorized');
        }

        $order->load(['revisions' => function ($q) {
            $q->orderByDesc('requested_at')->orderByDesc('id');
        }]);

        $allowed = $order->allowedRevisions();
        $used = $order->revisions->where('status', '!=', ServiceOfferOrderRevisionStatus::Rejected)->count();

        return [
            'order_id' => $order->id,
            'revisions_allowed' => $allowed,
            'revisions_used' => $used,
            'revisions_remaining' => max(0, $allowed - $used),
            'revisions' => $order->revisions,
        ];
    }

    /**
     * When order is in REVISION_REQUESTED, a pending revision row must exist before a new delivery is recorded.
     */
    public function assertPendingRevisionExistsForDelivery(ServiceOfferOrder $order): void
    {
        if ($order->status !== ServiceOfferOrderStatus::InRevision) {
            return;
        }

        $pendingRevision = $order->revisions()
            ->where('status', ServiceOfferOrderRevisionStatus::Accepted)
            ->lockForUpdate()
            ->first();

        if (! $pendingRevision) {
            throw new HttpException(422, 'No accepted revision available for this order');
        }
    }

    /**
     * After uploading delivery files for a revision cycle, mark the open revision request as fulfilled.
     */
    public function markPendingRevisionsAcceptedAfterDelivery(ServiceOfferOrder $order): void
    {
        if ($order->status !== ServiceOfferOrderStatus::RevisionRequested) {
            return;
        }

        $order->revisions()
            ->where('status', ServiceOfferOrderRevisionStatus::Pending)
            ->update([
                'status' => ServiceOfferOrderRevisionStatus::Accepted,
                'responded_at' => now(),
            ]);
    }

    public function acceptRevisionRequest(int $providerId, int $revisionId): ServiceOfferOrder
    {
        return DB::transaction(function () use ($providerId, $revisionId) {

            $revision = ServiceOfferOrderRevision::query()
                ->whereKey($revisionId)
                ->lockForUpdate()
                ->firstOrFail();

            $order = ServiceOfferOrder::query()
                ->whereKey($revision->service_offer_order_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Authorization
            if ($order->provider_id !== $providerId) {
                throw new HttpException(403, 'Unauthorized');
            }

            // Validation
            if ($revision->status !== ServiceOfferOrderRevisionStatus::Pending) {
                throw new HttpException(422, 'This revision request is no longer pending');
            }

            if ($order->status !== ServiceOfferOrderStatus::RevisionRequested) {
                throw new HttpException(422, 'Order is not awaiting a revision response');
            }

            // Accept revision
            $revision->update([
                'status' => ServiceOfferOrderRevisionStatus::Accepted,
                'responded_at' => now(),
            ]);

            // Move order back to progress
            $this->orderRepository->update([
                'status' => ServiceOfferOrderStatus::InRevision,
            ], $order);

            return $this->orderRepository->findById($order->id);
        });
    }
}
