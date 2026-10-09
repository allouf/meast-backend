<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\DiscoverCustomRequestsRequest;
use Modules\Services\Http\Resources\Provider\CustomRequestDiscoverDetailResource;
use Modules\Services\Http\Resources\Provider\CustomRequestDiscoverListResource;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Services\CustomRequestService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomRequestDiscoverController extends Controller
{
    public function __construct(
        protected CustomRequestService $customRequestService,
        protected ServiceProviderService $serviceProviderService,
    ) {}

    protected function resolveProviderContext(): array
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            throw new HttpException(401, __('services::messages.service_provider.not_found'));
        }

        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }

        return ['user_id' => $user->id, 'service_provider_id' => $profile->id];
    }

    public function index(DiscoverCustomRequestsRequest $request)
    {
        try {
            $context = $this->resolveProviderContext();
            $validated = $request->validated();
            $filterKeys = ['category_id', 'deadline_from', 'deadline_to', 'deadline_preset', 'budget_min', 'budget_max'];
            $filters = array_intersect_key($validated, array_flip($filterKeys));
            $perPage = (int) ($validated['per_page'] ?? 15);

            $paginator = $this->customRequestService->discoverRequestsForProvider(
                $context['user_id'],
                $context['service_provider_id'],
                $filters,
                $perPage
            );

            return $this->successResponse(
                __('services::messages.custom_request.discover_list_retrieved'),
                [
                    'requests' => CustomRequestDiscoverListResource::collection($paginator->items()),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'has_more' => $paginator->hasMorePages(),
                    ],
                ]
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(CustomRequest $customRequest)
    {
        try {
            $context = $this->resolveProviderContext();
            $rfq = $this->customRequestService->discoverRequestShowForProvider(
                $context['user_id'],
                $context['service_provider_id'],
                $customRequest->id
            );

            return $this->successResponse(
                __('services::messages.custom_request.discover_detail_retrieved'),
                new CustomRequestDiscoverDetailResource($rfq)
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
