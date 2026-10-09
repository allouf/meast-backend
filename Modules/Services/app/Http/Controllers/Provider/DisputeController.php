<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Services\Http\Requests\Provider\OpenDisputeRequest;
use Modules\Services\Http\Resources\Provider\DisputeListResource;
use Modules\Services\Http\Resources\Provider\DisputeResource;
use Modules\Services\Models\ServiceOfferOrder;
use Modules\Services\Services\DisputeService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DisputeController extends Controller
{

    public function __construct(
        protected DisputeService $disputeService
    ) {}

    public function store(OpenDisputeRequest $request, ServiceOfferOrder $order)
    {
        try {
            $dispute = $this->disputeService->openDispute(
                $order,
                $request->validated(),
                auth()->user()->id
            );


            return $this->successResponse(
                __('services::messages.dispute.opened_successfully'),
                new DisputeResource($dispute)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function myDisputes(Request $request)
    {
        try {
            $paginator = $this->disputeService->getMyDisputes(
                auth()->id(),
                $request->get('per_page', 15)
            );

            return $this->successResponse(
                'My disputes retrieved successfully',
                [
                    'diputes' => DisputeListResource::collection($paginator->items()),

                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                        'has_more'     => $paginator->hasMorePages(),
                    ],
                ]
            );
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        try {
            $dispute = $this->disputeService->getDisputeById(
                $id,
                auth()->id()
            );

            return $this->successResponse(
                'Dispute retrieved successfully',
                new DisputeResource($dispute)
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
