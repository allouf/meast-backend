<?php

namespace Modules\Services\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\DisputeValidationType;
use Modules\Services\Enums\ServiceOfferOrderDisputeEvidenceType;
use Modules\Services\Enums\ServiceOfferOrderDisputeStatus;
use Modules\Wallet\Enums\EscrowStatus;
use Modules\Services\Enums\ServiceOfferOrderRevisionStatus;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\DisputeReason;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Services\Models\ServiceOfferOrderDispute;
use Modules\Services\Repositories\Interfaces\DisputeRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DisputeService
{
    public function __construct(
        private DisputeRepositoryInterface $disputeRepository,
        private ServiceOfferOrderRepositoryInterface $orderRepository
    ) {}


    public function openDispute(ServiceOfferOrder $order, array $data, int $userId): ServiceOfferOrderDispute
    {
        return DB::transaction(function () use ($order, $data, $userId) {

            $order = $this->orderRepository->findById($order->id);

            // 1. Validate
            $this->validate($order, $data);

            $openedBy = $this->resolveOpenedBy($order, $userId);


            // 2. Create dispute
            $dispute = $this->disputeRepository->create([
                'service_offer_order_id' => $order->id,
                'buyer_id' => $order->buyer_id,
                'provider_id' => $order->provider_id,
                'opened_by' => $this->resolveOpenedBy($order, $userId),
                'reason_id' => $data['reason_id'],
                'description' => $data['description'] ?? null,
                'status' => ServiceOfferOrderDisputeStatus::Open,
                'frozen_at' => now(),
            ]);

            // 3. Freeze escrow
            $this->freezeEscrow($order);

            // 4. Lock order
            $order->update([
                'status' => ServiceOfferOrderStatus::Disputed
            ]);


            // 5. Lock revision flow (Edge Case)
            $this->lockRevisions($order);

            // 6. Attach evidence
            $this->attachEvidence($order, $dispute);

            return $dispute;
        });
    }

    private function resolveOpenedBy(ServiceOfferOrder $order, int $userId): string
    {
        if ($order->buyer_id === $userId) {
            return 'buyer';
        }

        if ($order->provider->user_id === $userId) {
            return 'provider';
        }

        throw new HttpException(403, 'Unauthorized to open dispute for this order');
    }

    /* =========================================================
         | Validation Layer
         * ========================================================= */
    private function validate(ServiceOfferOrder $order, array $data): void
    {
        $this->ensureOrderFunded($order);
        $this->ensureNoActiveDispute($order);
        $this->ensureNotExpired($order);
        $this->validateScenario($order, $data);
    }

    private function ensureOrderFunded(ServiceOfferOrder $order): void
    {
        if (!in_array($order->status, [
            ServiceOfferOrderStatus::InProgress,
            ServiceOfferOrderStatus::Delivered,
        ])) {
            throw new HttpException(400, 'Order is not funded');
        }
    }

    private function ensureNoActiveDispute(ServiceOfferOrder $order): void
    {
        if ($order->disputes()
            ->whereIn('status', [
                ServiceOfferOrderDisputeStatus::Open,
                ServiceOfferOrderDisputeStatus::UnderReview
            ])->exists()
        ) {
            throw new HttpException(409, 'Dispute already exists');
        }
    }

    private function ensureNotExpired(ServiceOfferOrder $order): void
    {
        if ($order->status === ServiceOfferOrderStatus::Completed) {

            $deadline = $order->updated_at->addDays(3);

            if (now()->greaterThan($deadline)) {
                throw new HttpException(403, 'Dispute window expired');
            }
        }
    }

    private function validateScenario(ServiceOfferOrder $order, array $data): void
    {
        $reason = DisputeReason::findOrFail($data['reason_id']);

        $type = $reason->validation_type->value;

        $method = $this->validators()[$type] ?? null;

        if (!$method || !method_exists($this, $method)) {
            throw new HttpException(422, 'Invalid dispute validation type');
        }

        $this->{$method}($order, $data);
    }

    private function validators(): array
    {
        return [
            DisputeValidationType::LateDelivery->value => 'validateLateDelivery',
            DisputeValidationType::BadDelivery->value => 'validateBadDelivery',
            DisputeValidationType::ScopeCreep->value => 'validateScopeCreep',
        ];
    }


    private function validateLateDelivery(ServiceOfferOrder $order): void
    {
        if (now()->lessThanOrEqualTo($order->deadline)) {
            throw new HttpException(422, 'Deadline not passed');
        }

        if ($order->extensions()->where('status', 'approved')->exists()) {
            throw new HttpException(409, 'Extension exists');
        }
    }

    private function validateBadDelivery(ServiceOfferOrder $order): void
    {
        if (!$order->deliveries()->exists()) {
            throw new HttpException(404, 'No delivery found');
        }
    }

    private function validateScopeCreep(ServiceOfferOrder $order): void
    {
        $hasRevisions = $order->revisions()->exists();

        if (!$hasRevisions) {
            throw new HttpException(422, 'No revision evidence found for scope creep');
        }
    }

    /* =========================================================
         | Actions
         * ========================================================= */
    private function freezeEscrow(ServiceOfferOrder $order): void
    {
        $order->escrows()->update([
            'status' => EscrowStatus::Disputed,
        ]);
    }

    private function lockRevisions(ServiceOfferOrder $order): void
    {
        $order->revisions()
            ->whereIn('status', [
                ServiceOfferOrderRevisionStatus::Pending
            ])
            ->update([
                'status' => ServiceOfferOrderRevisionStatus::Locked
            ]);
    }

    /* =========================================================
         | Evidence Builder
         * ========================================================= */
    private function attachEvidence(ServiceOfferOrder $order, ServiceOfferOrderDispute $dispute): void
    {
        $order->loadMissing([
            'revisions',
            'extensions',
            'deliveries',
        ]);

        $this->attachFromRelation($order->revisions, ServiceOfferOrderDisputeEvidenceType::Revision, $dispute);

        $this->attachFromRelation($order->extensions, ServiceOfferOrderDisputeEvidenceType::Extension, $dispute);

        $this->attachFromRelation($order->deliveries, ServiceOfferOrderDisputeEvidenceType::Delivery, $dispute);
    }

    private function attachFromRelation($collection, ServiceOfferOrderDisputeEvidenceType $type, $dispute): void
    {
        foreach ($collection as $item) {
            $this->disputeRepository->addEvidence($dispute, [
                'type' => $type,
                'reference_id' => $item->id,
                'data_snapshot' => $item->toArray(),
            ]);
        }
    }

    public function getMyDisputes(int $userId, int $perPage = 15):LengthAwarePaginator
    {
        return $this->disputeRepository->findByUser($userId, $perPage);
    }


    public function getDisputeById(int $disputeId, int $userId): ServiceOfferOrderDispute
    {
        $dispute = $this->disputeRepository->findById($disputeId);

        $this->authorizeAccess($dispute, $userId);

        return $this->disputeRepository->findWithDetails($disputeId);
    }

    private function authorizeAccess(ServiceOfferOrderDispute $dispute, int $userId): void
    {
        $isBuyer = $dispute->buyer_id === $userId;

        $isProvider = $dispute->provider?->user_id === $userId;

        if (!$isBuyer && !$isProvider) {
            throw new HttpException(403, 'Unauthorized access to dispute');
        }
    }
}
