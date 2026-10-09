<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Enums\BoostCampaignStatus;
use Modules\Services\Http\Requests\Provider\StoreBoostCampaignRequest;
use Modules\Services\Http\Resources\Provider\BoostCampaignResource;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Services\BoostCampaignService;
use Modules\Services\Services\ServiceProviderService;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BoostCampaignController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService,
        protected BoostCampaignService $boostCampaignService
    ) {}

    /**
     * Paginated boost campaigns for the authenticated provider (“my boosts”).
     */
    public function index(Request $request)
    {
        try {
            $provider = $this->getProfile();
            $validated = $request->validate([
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'status' => ['sometimes', 'nullable', 'string', Rule::in(BoostCampaignStatus::values())],
            ]);

            $filters = [
                'per_page' => (int) ($validated['per_page'] ?? 15),
                'page' => (int) ($validated['page'] ?? 1),
            ];

            if (! empty($validated['status'])) {
                $filters['status'] = $validated['status'];
            }

            $result = $this->boostCampaignService->listForProvider($provider, $filters);

            return $this->successResponse(
                __('services::messages.boost_campaigns.retrieved_successfully'),
                [
                    'boost_campaigns' => BoostCampaignResource::collection($result->items()),
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
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

    /**
     * Activate (or extend) boost on a published listing. Payment is out of band.
     */
    public function store(StoreBoostCampaignRequest $request)
    {
        try {
            $provider = $this->getProfile();
            $campaign = $this->boostCampaignService->createForProvider($provider, $request->validated());
            $campaign->load(['offer', 'plan']);

            return $this->successResponse(
                __('services::messages.boost_campaigns.created_successfully'),
                new BoostCampaignResource($campaign)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    protected function getProfile(): ServiceProvider
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            throw new HttpException(401, __('services::messages.service_provider.not_found'));
        }
        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }

        return $profile;
    }
}
