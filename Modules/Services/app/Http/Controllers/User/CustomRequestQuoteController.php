<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Services\Http\Resources\User\CustomRequestOrderResource;
use Modules\Services\Models\Quote;
use Modules\Services\Services\CustomRequestService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomRequestQuoteController extends Controller
{
    public function __construct(
        protected CustomRequestService $customRequestService
    ) {}

    public function accept(Quote $quote)
    {
        try {
            $order = $this->customRequestService->acceptQuoteForClient(auth()->id(), $quote->id);

            return $this->successResponse(
                __('services::messages.custom_request.quote_accepted'),
                new CustomRequestOrderResource($order),
                201
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
