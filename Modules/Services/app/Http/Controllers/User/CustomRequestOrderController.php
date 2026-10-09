<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Services\Http\Requests\User\ProcessCustomRequestOrderPaymentRequest;
use Modules\Services\Http\Resources\User\CustomRequestOrderResource;
use Modules\Services\Services\CustomRequestOrderService;

class CustomRequestOrderController extends Controller
{
    public function __construct(
        protected CustomRequestOrderService $customRequestOrderService
    ) {}

    public function show(int $id)
    {
        $order = $this->customRequestOrderService->getDetailsForParty(auth()->id(), $id);

        return $this->successResponse(
            __('services::messages.custom_request_order.retrieved_successfully'),
            new CustomRequestOrderResource($order)
        );
    }

    public function processPayment(int $id, ProcessCustomRequestOrderPaymentRequest $request)
    {
        $order = $this->customRequestOrderService->processPaymentForClient(
            auth()->id(),
            $id,
            $request->validated('payment_method')
        );

        return $this->successResponse(
            __('services::messages.custom_request_order.payment_processed'),
            new CustomRequestOrderResource($order)
        );
    }
}
