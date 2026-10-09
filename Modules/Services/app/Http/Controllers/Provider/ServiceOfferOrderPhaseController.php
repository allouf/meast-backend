<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Modules\Services\Http\Requests\Provider\DeliverServiceOfferOrderRequest;
use Modules\Services\Http\Resources\Provider\ServiceOfferOrderDetailsResource;
use Modules\Services\Services\MilestoneOrderPhaseService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferOrderPhaseController extends Controller
{
    public function __construct(
        protected MilestoneOrderPhaseService $phaseService,
    ) {}

    public function start(int $order, int $phase)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;
            $orderModel = $this->phaseService->startPhase($providerId, $order, $phase);

            return $this->successResponse(
                __('services::messages.milestone_phase.started_successfully'),
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

    public function deliver(int $order, int $phase, DeliverServiceOfferOrderRequest $request)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;
            $orderModel = $this->phaseService->deliverPhase(
                $providerId,
                $order,
                $phase,
                $request->validated('message'),
                $request->file('attachments', [])
            );

            return $this->successResponse(
                __('services::messages.milestone_phase.delivered_successfully'),
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
