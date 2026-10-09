<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\ListProviderCustomRequestQuotesRequest;
use Modules\Services\Http\Requests\Provider\StoreCustomRequestQuoteRequest;
use Modules\Services\Http\Requests\Provider\UpdateProviderCustomRequestQuoteRequest;
use Modules\Services\Http\Resources\Provider\ProviderCustomRequestQuoteListResource;
use Modules\Services\Http\Resources\Provider\ProviderCustomRequestQuoteSummaryResource;
use Modules\Services\Http\Resources\User\CustomRequestQuoteResource;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Models\Quote;
use Modules\Services\Services\CustomRequestService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomRequestQuoteController extends Controller
{
    public function __construct(
        protected CustomRequestService $customRequestService,
        protected ServiceProviderService $serviceProviderService,
    ) {}

    protected function getServiceProviderId(): int
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            throw new HttpException(401, __('services::messages.service_provider.not_found'));
        }

        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }

        return (int) $profile->id;
    }

    public function stats()
    {
        $stats = $this->customRequestService->providerQuotesStats($this->getServiceProviderId());

        return $this->successResponse(
            __('services::messages.custom_request_quote.stats_retrieved'),
            $stats
        );
    }

    public function index(ListProviderCustomRequestQuotesRequest $request)
    {
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        $paginator = $this->customRequestService->providerQuotesList($this->getServiceProviderId(), $perPage);

        return $this->successResponse(
            __('services::messages.custom_request_quote.list_retrieved'),
            ProviderCustomRequestQuoteListResource::collection($paginator)
        );
    }

    public function store(CustomRequest $customRequest, StoreCustomRequestQuoteRequest $request)
    {
        try {
            $user = Auth::guard('api')->user();
            $quote = $this->customRequestService->submitQuoteForProvider(
                $user->id,
                $this->getServiceProviderId(),
                $customRequest,
                $request->validated()
            );

            return $this->successResponse(
                __('services::messages.custom_request_quote.submitted_successfully'),
                new CustomRequestQuoteResource($quote),
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

    public function update(Quote $quote, UpdateProviderCustomRequestQuoteRequest $request)
    {
        $updated = $this->customRequestService->updateProviderQuote(
            $this->getServiceProviderId(),
            $quote->id,
            $request->validated()
        );

        return $this->successResponse(
            __('services::messages.custom_request_quote.updated_successfully'),
            new ProviderCustomRequestQuoteSummaryResource($updated)
        );
    }

    public function cancel(Quote $quote)
    {
        $updated = $this->customRequestService->cancelProviderQuote(
            $this->getServiceProviderId(),
            $quote->id
        );

        return $this->successResponse(
            __('services::messages.custom_request_quote.canceled_successfully'),
            new ProviderCustomRequestQuoteSummaryResource($updated)
        );
    }
}
