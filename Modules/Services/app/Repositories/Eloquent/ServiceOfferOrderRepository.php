<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Enums\OrderableType;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Models\Milestone;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;

class ServiceOfferOrderRepository implements ServiceOfferOrderRepositoryInterface
{
    public function __construct(
        protected ServiceOfferOrder $model
    ) {}

    public function all()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        return $this->model
            ->with([
                'buyer',
                'provider.user',
                'latestEscrow',
                'deliveries',
                'rejectionReason',
                'extensions.reason',
                'revisions' => function ($q) {
                    $q->orderByDesc('requested_at')->orderByDesc('id');
                },
                'phases' => function ($q) {
                    $q->orderBy('sort_order')
                        ->with(['latestEscrow', 'serviceCategory', 'deliveries']);
                },
                'currentPhase.latestEscrow',
                'referenceServiceOffer.coverMedia',
                'referenceServiceOffer.serviceCategory',
                'orderable' => function ($morphTo) {
                    $morphTo->morphWith([
                        ServiceOffer::class => [
                            'coverMedia',
                            'serviceCategory',
                        ],
                        Milestone::class => [
                            'milestoneServices.serviceCategory',
                            'serviceProvider.user',
                            'serviceOfferOrder',
                        ],
                    ]);
                },
            ])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, ServiceOfferOrder $serviceOrder)
    {
        $serviceOrder->update($data);

        return $this->findById($serviceOrder->id);
    }

    public function delete(ServiceOfferOrder $serviceOrder)
    {
        return $serviceOrder->delete();
    }

    public function getOrdersByBuyer(int $buyerId, ?ServiceOfferOrderStatus $status = null)
    {
        $query = $this->model->where('buyer_id', $buyerId)
            ->with([
                'provider.user',
                'referenceServiceOffer.coverMedia',
                'orderable' => function ($morphTo) {
                    $morphTo->morphWith([
                        ServiceOffer::class => ['coverMedia'],
                        Milestone::class => [
                            'milestoneServices.serviceCategory',
                            'serviceProvider.user',
                        ],
                    ]);
                },
                'latestEscrow',
                'deliveries',
                'rejectionReason',
            ]);

        if ($status) {
            if ($status === ServiceOfferOrderStatus::Pending) {
                $query->whereIn('status', [
                    ServiceOfferOrderStatus::Pending->value,
                    ServiceOfferOrderStatus::PendingPayment->value,
                ]);
            } else {
                $query->where('status', $status->value);
            }
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getOrdersByProvider(int $providerId, ?ServiceOfferOrderStatus $status = null)
    {
        $query = $this->model
            ->where('provider_id', $providerId)
            ->with([
                'buyer:id,name,photo,country_code',
                'referenceServiceOffer',
                'orderable',
                'latestEscrow',
                'deliveries',
            ]);

        if ($status) {
            $query->where('status', $status->value);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getLastOrderOfYear(string $year): ?ServiceOfferOrder
    {
        return $this->model
            ->where('order_number', 'like', "ORD-{$year}-%")
            ->orderByDesc('order_number')
            ->first();
    }

    public function getOrdersByService(int $serviceOfferId, ?ServiceOfferOrderStatus $status = null)
    {
        $query = $this->model
            ->where('orderable_type', OrderableType::ServiceOffer->value)
            ->where('orderable_id', $serviceOfferId)
            ->with([
                'buyer',
                'provider.user',
                'orderable' => function ($morphTo) {
                    $morphTo->morphWith([
                        ServiceOffer::class => [
                            'coverMedia',
                            'serviceCategory',
                        ],
                    ]);
                },
                'latestEscrow',
                'deliveries',
            ]);

        if ($status) {
            $query->where('status', $status->value);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function findNegotiationOrderForProvider(int $providerId, int $orderId): ?ServiceOfferOrder
    {
        return $this->model->newQuery()
            ->whereKey($orderId)
            ->where('provider_id', $providerId)
            ->where('status', ServiceOfferOrderStatus::Negotiation->value)
            ->whereNull('orderable_type')
            ->whereNull('orderable_id')
            ->first();
    }
}
