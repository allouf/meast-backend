<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\StoreMilestoneRequest;
use Modules\Services\Http\Requests\Provider\StoreMilestoneServiceLineRequest;
use Modules\Services\Http\Requests\Provider\UpdateMilestoneRequest;
use Modules\Services\Http\Requests\Provider\UpdateMilestoneServiceLineRequest;
use Modules\Services\Http\Resources\Provider\MilestoneListResource;
use Modules\Services\Http\Resources\Provider\MilestoneResource;
use Modules\Services\Http\Resources\Provider\MilestoneServiceLineResource;
use Modules\Services\Services\MilestoneService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MilestoneController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService,
        protected MilestoneService $milestoneService,
    ) {}

    protected function getProfile()
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

    public function index(Request $request)
    {
        try {
            $profile = $this->getProfile();
            $filters = $request->only(['per_page', 'page', 'search', 'activation_status', 'delivery_model']);
            $result = $this->milestoneService->listForProvider($profile, $filters);

            return $this->successResponse(
                __('services::messages.milestones.retrieved_successfully'),
                [
                    'milestones' => MilestoneListResource::collection($result->items()),
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

    public function show(int $id)
    {
        try {
            $profile = $this->getProfile();
            $milestone = $this->milestoneService->findMilestoneOrFail($id, $profile->id);

            return $this->successResponse(
                __('services::messages.milestones.retrieved_successfully'),
                new MilestoneResource($milestone)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function store(StoreMilestoneRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $milestone = $this->milestoneService->create($profile, $request->validated());

            return $this->successResponse(
                __('services::messages.milestones.created_successfully'),
                new MilestoneResource($milestone),
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

    public function update(UpdateMilestoneRequest $request, int $id)
    {
        try {
            $profile = $this->getProfile();
            $milestone = $this->milestoneService->findMilestoneOrFail($id, $profile->id);
            $milestone = $this->milestoneService->update($milestone, $request->validated());

            return $this->successResponse(
                __('services::messages.milestones.updated_successfully'),
                new MilestoneResource($milestone)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $profile = $this->getProfile();
            $milestone = $this->milestoneService->findMilestoneOrFail($id, $profile->id);
            $this->milestoneService->delete($milestone);

            return $this->successResponse(__('services::messages.milestones.deleted_successfully'), null);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function toggleActivation(int $id)
    {
        try {
            $profile = $this->getProfile();
            $milestone = $this->milestoneService->findMilestoneOrFail($id, $profile->id);
            $milestone = $this->milestoneService->toggleActivation($milestone);

            return $this->successResponse(
                __('services::messages.milestones.activation_toggled'),
                new MilestoneResource($milestone)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function storeServiceLine(StoreMilestoneServiceLineRequest $request, int $milestoneId)
    {
        try {
            $profile = $this->getProfile();
            $milestone = $this->milestoneService->findMilestoneOrFail($milestoneId, $profile->id);
            $line = $this->milestoneService->addServiceLine($milestone, $request->validated());
            $line->load('serviceCategory');

            return $this->successResponse(
                __('services::messages.milestones.service_line_created'),
                new MilestoneServiceLineResource($line),
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

    public function updateServiceLine(UpdateMilestoneServiceLineRequest $request, int $lineId)
    {
        try {
            $profile = $this->getProfile();
            $line = $this->milestoneService->findServiceLineOrFail($lineId, $profile->id);
            $line = $this->milestoneService->updateServiceLine($line, $request->validated());

            return $this->successResponse(
                __('services::messages.milestones.service_line_updated'),
                new MilestoneServiceLineResource($line)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function destroyServiceLine(int $lineId)
    {
        try {
            $profile = $this->getProfile();
            $line = $this->milestoneService->findServiceLineOrFail($lineId, $profile->id);
            $this->milestoneService->deleteServiceLine($line);

            return $this->successResponse(__('services::messages.milestones.service_line_deleted'), null);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
