<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\StoreProviderProjectRequest;
use Modules\Services\Http\Requests\Provider\UpdateProviderProjectRequest;
use Modules\Services\Http\Resources\Provider\ProviderProjectResource;
use Modules\Services\Services\ProviderProjectService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProviderProjectController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService,
        protected ProviderProjectService $providerProjectService
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

    /**
     * List my portfolio projects.
     */
    public function index(Request $request)
    {
        try {
            $profile = $this->getProfile();

            $filters = $request->only([
                'category_id',
                'date_range',
                'search',
            ]);

            $result = $this->providerProjectService->getByProvider($profile, $filters);
            return $this->successResponse(
                __('services::messages.provider_project.retrieved_successfully'),
                [
                    'projects' => ProviderProjectResource::collection($result->items()),
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
     * Show one project.
     */
    public function show(int $id)
    {
        try {
            $profile = $this->getProfile();
            $project = $this->providerProjectService->getById($id, $profile->id);
            if (! $project) {
                return $this->errorResponse(__('services::messages.provider_project.not_found'), 404);
            }
            return $this->successResponse(
                __('services::messages.provider_project.retrieved_successfully'),
                new ProviderProjectResource($project)
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
     * Create portfolio project.
     */
    public function store(StoreProviderProjectRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $images = $request->file('images');
            if (! is_array($images)) {
                $images = $images ? [$images] : [];
            }
            $data = $request->validated();
            unset($data['images']);
            $serviceOfferIds = $data['service_offer_ids'] ?? [];
            unset($data['service_offer_ids']);
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $project = $this->providerProjectService->create($profile, $data, $images, $serviceOfferIds);
            return $this->successResponse(
                __('services::messages.provider_project.created_successfully'),
                new ProviderProjectResource($project),
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

    /**
     * Update portfolio project.
     */
    public function update(UpdateProviderProjectRequest $request, int $id)
    {
        try {
            $profile = $this->getProfile();
            $project = $this->providerProjectService->getById($id, $profile->id);
            if (! $project) {
                return $this->errorResponse(__('services::messages.provider_project.not_found'), 404);
            }
            $images = $request->file('images');
            if (! is_array($images)) {
                $images = $images ? [$images] : null;
            }
            $data = $request->validated();
            unset($data['images']);
            $serviceOfferIds = array_key_exists('service_offer_ids', $data) ? $data['service_offer_ids'] : null;
            unset($data['service_offer_ids']);

            $project = $this->providerProjectService->update($project, $data, $images, $serviceOfferIds);
            return $this->successResponse(
                __('services::messages.provider_project.updated_successfully'),
                new ProviderProjectResource($project)
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
     * Delete portfolio project.
     */
    public function destroy(int $id)
    {
        try {
            $profile = $this->getProfile();
            $project = $this->providerProjectService->getById($id, $profile->id);
            if (! $project) {
                return $this->errorResponse(__('services::messages.provider_project.not_found'), 404);
            }
            $this->providerProjectService->delete($project);
            return $this->successResponse(__('services::messages.provider_project.deleted_successfully'), null);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
