<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Services\Http\Requests\Provider\RejectServiceOfferOrderRevisionRequest;
use Modules\Services\Http\Resources\Provider\ServiceOfferOrderDetailsResource;
use Modules\Services\Http\Resources\User\ServiceOfferOrderRevisionResource;
use Modules\Services\Models\ServiceOfferOrderRevision;
use Modules\Services\Services\MilestoneOrderPhaseService;
use Modules\Services\Services\ServiceOfferOrderRevisionService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferOrderRevisionController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderRevisionService $revisionService,
        protected MilestoneOrderPhaseService $phaseService,
    ) {}

    public function index(int $order)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $payload = $this->revisionService->getOrderRevisions(
                $providerId,
                $order,
                'provider'
            );

            return $this->successResponse(
                __('services::messages.order.revisions_fetched_successfully'),
                [
                    'order_id' => $payload['order_id'],
                    'revisions_allowed' => $payload['revisions_allowed'],
                    'revisions_used' => $payload['revisions_used'],
                    'revisions_remaining' => $payload['revisions_remaining'],
                    'revisions' => ServiceOfferOrderRevisionResource::collection($payload['revisions']),
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function reject(RejectServiceOfferOrderRevisionRequest $request, int $revision)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $revisionModel = ServiceOfferOrderRevision::query()->findOrFail($revision);

            $order = $revisionModel->service_offer_order_phase_id
                ? $this->phaseService->rejectPhaseRevision(
                    $providerId,
                    $revision,
                    $request->validated('rejection_reason')
                )
                : $this->revisionService->rejectRevisionRequest(
                    $providerId,
                    $revision,
                    $request->validated('rejection_reason')
                );

            return $this->successResponse(
                __('services::messages.order.revision_rejected_successfully'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function accept(int $revision)
    {
        try {
            $providerId = auth()->user()->serviceProvider->id;

            $revisionModel = ServiceOfferOrderRevision::query()->findOrFail($revision);

            $order = $revisionModel->service_offer_order_phase_id
                ? $this->phaseService->acceptPhaseRevision($providerId, $revision)
                : $this->revisionService->acceptRevisionRequest($providerId, $revision);

            return $this->successResponse(
                __('services::messages.order.revision_accepted_successfully'),
                new ServiceOfferOrderDetailsResource($order)
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
