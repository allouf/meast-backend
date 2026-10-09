<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Services\Http\Requests\User\StoreServiceOfferOrderRevisionRequest;
use Modules\Services\Http\Resources\User\ServiceOfferOrderDetailsResource;
use Modules\Services\Http\Resources\User\ServiceOfferOrderRevisionResource;
use Modules\Services\Services\ServiceOfferOrderRevisionService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferOrderRevisionController extends Controller
{
    public function __construct(
        protected ServiceOfferOrderRevisionService $revisionService
    ) {}

    public function index(int $order)
    {
        try {
            $payload = $this->revisionService->getOrderRevisions(
                auth()->id(),
                $order,
                'buyer'
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

    public function store(StoreServiceOfferOrderRevisionRequest $request, int $order)
    {
        try {
            $data = $request->validated();
            $data['attachments'] = $request->file('attachments', []);

            $orderModel = $this->revisionService->requestRevision(
                auth()->id(),
                $order,
                $data
            );

            return $this->successResponse(
                __('services::messages.order.revision_requested_successfully'),
                new ServiceOfferOrderDetailsResource($orderModel)
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
