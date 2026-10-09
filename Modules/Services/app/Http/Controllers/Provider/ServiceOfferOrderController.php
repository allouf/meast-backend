<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Services\Enums\ServiceOfferOrderStatus;
use Modules\Services\Http\Requests\Provider\DeliverServiceOfferOrderRequest;
use Modules\Services\Http\Requests\Provider\RejectServiceOfferOrderRequest;
use Modules\Services\Http\Requests\Provider\StoreMilestoneRequest;
use Modules\Services\Http\Resources\Provider\ServiceOfferOrderDetailsResource;
use Modules\Services\Http\Resources\Provider\ServiceOfferOrderListResource;
use Modules\Services\Services\MilestoneOrderPhaseService;
use Modules\Services\Services\ServiceOfferOrderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferOrderController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderService $serviceOfferOrderService,
        protected MilestoneOrderPhaseService $phaseService,
    ) {}


    public function index(Request $request)
    {
        $status = $request->query('status');
        $statusEnum = $status ? ServiceOfferOrderStatus::from($status) : null;

        $providerId = auth()->user()->serviceProvider->id;

        $orders = $this->serviceOfferOrderService->getProviderOrders(
            $providerId,
            $statusEnum
        );

        return $this->successResponse(
            __('services::messages.orders.fetched_successfully'),
            ServiceOfferOrderListResource::collection($orders)
        );
    }

    public function show(int $id)
    {
        $providerId = auth()->user()->serviceProvider->id;

        $order = $this->serviceOfferOrderService->getOrderDetails(
            $providerId,
            $id,
            'provider'
        );

        return $this->successResponse(
            __('services::messages.order.fetched_successfully'),
            new ServiceOfferOrderDetailsResource($order)
        );
    }

    public function serviceOrders(Request $request, int $serviceOfferId)
    {
        $status = $request->query('status');
        $statusEnum = $status ? ServiceOfferOrderStatus::from($status) : null;

        $providerId = auth()->user()->serviceProvider->id;

        $orders = $this->serviceOfferOrderService->getServiceOrders(
            $serviceOfferId,
            $providerId,
            $statusEnum
        );

        return $this->successResponse(
            __('services::messages.orders.fetched_successfully'),
            ServiceOfferOrderListResource::collection($orders)
        );
    }

    public function start(int $id)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $order = $this->serviceOfferOrderService->startOrder(
                $providerId,
                $id
            );

            return $this->successResponse(
                __('services::messages.order.started_successfully'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function reject(int $id, RejectServiceOfferOrderRequest $request)
    {

        try {
            $providerId = auth()->user()->serviceProvider->id;

            $order = $this->serviceOfferOrderService->rejectOrder(
                $providerId,
                $id,
                $request->validated('reason_id'),
                $request->validated('notes')
            );

            return $this->successResponse(
                __('services::messages.order.rejected_successfully'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deliver(int $id, DeliverServiceOfferOrderRequest $request)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $order = $this->serviceOfferOrderService->deliverOrder(
                $providerId,
                $id,
                $request->validated('message'),
                $request->file('attachments', [])
            );

            return $this->successResponse(
                __('services::messages.order.delivered_successfully'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }


    public function cancel(int $id)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $order = $this->serviceOfferOrderService->cancelOrderByProvider($providerId, $id);

            return $this->successResponse(
                __('services::messages.order.cancelled_successfully'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function changeStatus(Request $request, int $id)
    {
        $providerId = auth()->user()->serviceProvider->id;

        $request->validate([
            'status' => ['required', Rule::in(ServiceOfferOrderStatus::values())],
        ]);

        $order = $this->serviceOfferOrderService->changeStatus(
            $providerId,
            $id,
            $request->status
        );

        return $this->successResponse(
            'Order status updated successfully',
            new ServiceOfferOrderDetailsResource($order)
        );
    }

    public function requestExtension(Request $request, int $id)
    {
        $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:30'],
            'reason_id' => ['required', 'integer', 'exists:service_offer_order_extension_reasons,id'],
            'description' => ['nullable', 'string'],
        ]);

        $providerId = auth()->user()->serviceProvider->id;

        $order = $this->serviceOfferOrderService->requestExtension(
            $providerId,
            $id,
            $request->days,
            $request->reason_id,
            $request->description
        );

        return $this->successResponse(
            'Extension requested successfully',
            new ServiceOfferOrderDetailsResource($order)
        );
    }

    /**
     * POST /api/v1/provider-orders/{id}/define-milestone
     *
     * Provider, working on a buyer's status=Negotiation order, defines the
     * milestone template (title + description + delivery_model +
     * request_modification_days + services[]) inline. The service layer:
     *   1. Creates a Milestone template owned by this provider (becomes
     *      part of their template library — reusable later).
     *   2. Copies the template's milestone_services into
     *      service_offer_order_phases bound to this order.
     *   3. Sets orderable_type/orderable_id on the order to point at the
     *      new template, sets current_phase_id to the first phase.
     *   4. Transitions order status Negotiation -> PendingPayment so the
     *      buyer can pay the first phase via the existing per-phase pay
     *      endpoint.
     *
     * Validation rules are shared with create-milestone (StoreMilestoneRequest)
     * since the inline-define form has the same shape — title, delivery_model,
     * request_modification_days, services[].
     */
    public function defineMilestone(StoreMilestoneRequest $request, int $id)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $order = $this->phaseService->defineMilestoneForNegotiationOrder(
                $providerId,
                $id,
                $request->validated()
            );

            return $this->successResponse(
                __('services::messages.milestone_phase.negotiation_milestone_defined'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
