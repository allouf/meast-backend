<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Services\Http\Requests\User\StoreServiceOfferOrderRevisionRequest;
use Modules\Services\Http\Resources\User\ServiceOfferOrderDetailsResource;
use Modules\Services\Services\MilestoneOrderPhaseService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferOrderPhaseController extends Controller
{
    public function __construct(
        protected MilestoneOrderPhaseService $phaseService,
    ) {}

    public function pay(int $order, int $phase)
    {
        try {
            // payPhase mints a Stripe Connect Checkout for this phase and
            // returns ['order' => ..., 'invoice' => Invoice with checkout_url].
            // Frontend redirects the buyer to checkout_url; the phase escrow
            // is written by MarkServiceOrderPhasePaidOnInvoiceCompletion
            // when the webhook lands.
            $result = $this->phaseService->payPhase(auth()->id(), $order, $phase);

            return $this->successResponse(
                __('services::messages.milestone_phase.paid_successfully'),
                [
                    'order'        => new ServiceOfferOrderDetailsResource($result['order']),
                    'checkout_url' => $result['invoice']->checkout_url,
                ],
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function accept(int $order, int $phase)
    {
        try {
            $orderModel = $this->phaseService->acceptPhase(auth()->id(), $order, $phase);

            return $this->successResponse(
                __('services::messages.milestone_phase.accepted_successfully'),
                new ServiceOfferOrderDetailsResource($orderModel)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function requestRevision(StoreServiceOfferOrderRevisionRequest $request, int $order, int $phase)
    {
        try {
            $data = $request->validated();
            $data['attachments'] = $request->file('attachments', []);

            $orderModel = $this->phaseService->requestPhaseRevision(
                auth()->id(),
                $order,
                $phase,
                $data
            );

            return $this->successResponse(
                __('services::messages.order.revision_requested_successfully'),
                new ServiceOfferOrderDetailsResource($orderModel)
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
