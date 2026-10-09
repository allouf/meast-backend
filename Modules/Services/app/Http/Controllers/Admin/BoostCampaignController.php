<?php

namespace Modules\Services\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Admin\AdminBoostModerationRequest;
use Modules\Services\Http\Resources\Admin\AdminBoostCampaignResource;
use Modules\Services\Services\BoostCampaignService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BoostCampaignController extends Controller
{
    public function __construct(
        protected BoostCampaignService $boostCampaignService,
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only([
                'per_page',
                'page',
                'status',
                'statuses',
                'active_window_only',
                'provider_id',
                'service_offer_id',
                'search',
            ]);
            $result = $this->boostCampaignService->listCampaignsAdmin($filters);

            return $this->successResponse(
                __('services::messages.boost_campaigns.admin.retrieved_successfully'),
                [
                    'boost_campaigns' => AdminBoostCampaignResource::collection($result->items()),
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
                    ],
                ]
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(int $id)
    {
        try {
            $campaign = $this->boostCampaignService->findCampaignAdmin($id);

            return $this->successResponse(
                __('services::messages.boost_campaigns.admin.retrieved_successfully'),
                new AdminBoostCampaignResource($campaign)
            );
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse(__('services::messages.boost_campaigns.admin.not_found'), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function pause(AdminBoostModerationRequest $request, int $id)
    {
        try {
            $adminId = (int) Auth::guard('admin')->id();
            $campaign = $this->boostCampaignService->pauseCampaign(
                $id,
                $adminId,
                data_get($request->validated(), 'reason')
            );

            return $this->successResponse(
                __('services::messages.boost_campaigns.admin.paused_successfully'),
                new AdminBoostCampaignResource($campaign)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function terminate(AdminBoostModerationRequest $request, int $id)
    {
        try {
            $adminId = (int) Auth::guard('admin')->id();
            $out = $this->boostCampaignService->terminateCampaign(
                $id,
                $adminId,
                data_get($request->validated(), 'reason')
            );

            return $this->successResponse(
                __('services::messages.boost_campaigns.admin.terminated_successfully'),
                [
                    'campaign' => new AdminBoostCampaignResource($out['campaign']),
                    'notional_refund' => $out['notional_refund'],
                ]
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
