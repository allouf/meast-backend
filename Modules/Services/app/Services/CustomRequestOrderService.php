<?php

namespace Modules\Services\Services;

use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\CustomRequestOrderPaymentStatus;
use Modules\Services\Models\Order;
use Modules\Services\Repositories\Interfaces\CustomRequestOrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomRequestOrderService
{
    public function __construct(
        protected CustomRequestOrderRepositoryInterface $customRequestOrderRepository
    ) {}

    public function getDetailsForParty(int $userId, int $orderId): Order
    {
        return $this->customRequestOrderRepository->findForPartyOrFail($userId, $orderId);
    }

    public function processPaymentForClient(int $clientId, int $orderId, string $paymentMethod): Order
    {
        return DB::transaction(function () use ($clientId, $orderId, $paymentMethod) {
            $order = $this->customRequestOrderRepository->lockForClientOrFail($clientId, $orderId);

            if ($order->payment_status !== CustomRequestOrderPaymentStatus::PendingPayment) {
                throw new HttpException(422, __('services::messages.custom_request_order.payment_already_processed'));
            }

            return $this->customRequestOrderRepository->update($order, [
                'payment_method' => $paymentMethod,
                'payment_status' => CustomRequestOrderPaymentStatus::EscrowFunded,
            ]);
        });
    }
}
