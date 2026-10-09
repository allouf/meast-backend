<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Services\Enums\CustomRequestStatus;
use Modules\Services\Http\Requests\User\ListCustomRequestsRequest;
use Modules\Services\Http\Requests\User\StoreCustomRequestRequest;
use Modules\Services\Http\Requests\User\UpdateCustomRequestRequest;
use Modules\Services\Http\Resources\User\CustomRequestDetailResource;
use Modules\Services\Http\Resources\User\CustomRequestListResource;
use Modules\Services\Http\Resources\User\CustomRequestQuoteResource;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Services\CustomRequestService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomRequestController extends Controller
{
    public function __construct(
        protected CustomRequestService $customRequestService
    ) {}

    public function index(ListCustomRequestsRequest $request)
    {
        $validated = $request->validated();
        $status = isset($validated['status'])
            ? CustomRequestStatus::from($validated['status'])
            : null;
        $perPage = (int) ($validated['per_page'] ?? 15);

        $paginator = $this->customRequestService->listForClient(auth()->id(), $status, $perPage);

        return $this->successResponse(
            __('services::messages.custom_request.list_retrieved_successfully'),
            [
                'custom_requests' => CustomRequestListResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'has_more' => $paginator->hasMorePages(),
                ],
            ]
        );
    }

    public function store(StoreCustomRequestRequest $request)
    {
        $data = $request->validated();
        unset($data['attachments']);
        $files = $request->file('attachments', []) ?? [];
        if (! is_array($files)) {
            $files = [$files];
        }

        $rfq = $this->customRequestService->createForClient(auth()->id(), $data, $files);

        return $this->successResponse(
            __('services::messages.custom_request.created_successfully'),
            new CustomRequestDetailResource($rfq),
            201
        );
    }

    public function update(UpdateCustomRequestRequest $request, int $id)
    {
        try {
            $data = $request->validated();

            $files = $request->file('attachments', []) ?? [];
            if (!is_array($files)) {
                $files = [$files];
            }

            $rfq = $this->customRequestService->updateForClient(
                auth()->id(),
                $id,
                $data,
                $files
            );

            return $this->successResponse(
                __('services::messages.custom_request.updated_successfully'),
                new CustomRequestDetailResource($rfq)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Throwable $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        $rfq = $this->customRequestService->getDetailsForClient(auth()->id(), $id);

        return $this->successResponse(
            __('services::messages.custom_request.retrieved_successfully'),
            new CustomRequestDetailResource($rfq)
        );
    }

    public function cancel(int $id)
    {
        $rfq = $this->customRequestService->cancelForClient(auth()->id(), $id);

        return $this->successResponse(
            __('services::messages.custom_request.cancelled_successfully'),
            new CustomRequestDetailResource($rfq)
        );
    }

    public function quotes(CustomRequest $customRequest)
    {
        $rfq = $this->customRequestService->listQuotesForClient(auth()->id(), $customRequest->id);
        $rfq->loadCount('quotes');

        return $this->successResponse(
            __('services::messages.custom_request.quotes_retrieved_successfully'),
            [
                'custom_request' => new CustomRequestListResource($rfq),
                'quotes' => CustomRequestQuoteResource::collection($rfq->quotes),
            ]
        );
    }
}
