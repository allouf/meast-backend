<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Http\Requests\User\StoreServiceOfferOrderRequest;
use Modules\Services\Http\Resources\User\ServiceOfferOrderDetailsResource;
use Modules\Services\Http\Resources\User\ServiceOfferOrderListResource;
use Modules\Services\Services\ServiceOfferOrderService;

class ServiceOfferOrderController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderService $serviceOfferOrderService
    ) {}

    public function store(StoreServiceOfferOrderRequest $request)
    {
        // createOrder returns ['order' => ServiceOfferOrder, 'invoice' => ?Invoice].
        // For single-offer orders the invoice carries a Stripe Connect Checkout
        // session — frontend redirects the buyer to checkout_url. For milestone
        // orders invoice is null; payment happens later per-phase via
        // POST /service-orders/{order}/phases/{phase}/pay.
        $result = $this->serviceOfferOrderService->createOrder(
            auth()->id(),
            $request->validated()
        );

        return $this->successResponse(
            __('services::messages.order.created_successfully'),
            [
                'order'        => new ServiceOfferOrderDetailsResource($result['order']),
                'checkout_url' => $result['invoice']?->checkout_url,
            ],
            201
        );
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $statusEnum = $status ? ServiceOfferOrderStatus::from($status) : null;

        $orders = $this->serviceOfferOrderService->getUserOrders(auth()->id(), $statusEnum);

        return $this->successResponse(
            __('services::messages.orders.fetched_successfully'),
            ServiceOfferOrderListResource::collection($orders)
        );
    }

    public function show(int $id)
    {
        $order = $this->serviceOfferOrderService->getOrderDetails(
            auth()->id(),
            $id,
            'buyer'
        );

        return $this->successResponse(
            __('services::messages.order.fetched_successfully'),
            new ServiceOfferOrderDetailsResource($order)
        );
    }

    public function cancel(int $id)
    {
        $order = $this->serviceOfferOrderService->cancelOrder(
            auth()->id(),
            $id
        );

        return $this->successResponse(
            __('services::messages.order.cancelled_successfully'),
            new ServiceOfferOrderDetailsResource($order)
        );
    }

    /**
     * POST /api/v1/service-orders/accept/{id}
     *
     * Buyer confirms delivery of a single-offer order. Mirrors the
     * milestone-phase accept (MilestoneOrderPhaseService::acceptPhase)
     * for the non-milestone path which previously had no terminal
     * transition:
     *   - Verifies order belongs to the authenticated buyer.
     *   - Asserts order status = Delivered (the only accept-able state).
     *   - Refuses if a pending revision exists — that needs resolution
     *     before acceptance.
     *   - Escrow PAID -> RELEASED (the seller's funds are now formally
     *     theirs from our internal ledger's perspective).
     *   - Order Delivered -> Completed, completed_at = now.
     */
    public function accept(int $id)
    {
        $order = $this->serviceOfferOrderService->acceptOrder(
            auth()->id(),
            $id
        );

        return $this->successResponse(
            __('services::messages.order.accepted_successfully'),
            new ServiceOfferOrderDetailsResource($order)
        );
    }


    public function approveExtension(int $extensionId)
    {
        $order = $this->serviceOfferOrderService->approveExtension(
            auth()->id(),
            $extensionId
        );

        return $this->successResponse(
            'Extension approved successfully',
            new ServiceOfferOrderDetailsResource($order)
        );
    }

    public function rejectExtension(int $extensionId)
    {
        $order = $this->serviceOfferOrderService->rejectExtension(
            auth()->id(),
            $extensionId
        );

        return $this->successResponse(
            'Extension rejected successfully',
            new ServiceOfferOrderDetailsResource($order)
        );
    }
}
