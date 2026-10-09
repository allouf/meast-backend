<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\AddServiceOfferPlanRequest;
use Modules\Services\Http\Requests\Provider\UpdateServiceOfferPlanRequest;
use Modules\Services\Models\ServiceOfferPlan;
use Modules\Services\Services\ServiceOfferService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferPlanController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService,
        protected ServiceOfferService $serviceOfferService
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
     * Add a plan to a service offer.
     * POST service-offers/add-plan/{id}
     */
    public function store(int $id, AddServiceOfferPlanRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $plan = $this->serviceOfferService->addPlan($profile, $id, $request->validated());
            return $this->successResponse(
                __('services::messages.service_offers.plan_created_successfully'),
                $this->planToArray($plan),
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
     * Update a plan.
     * POST service-offers/update-plan/{planId}
     */
    public function update(int $planId, UpdateServiceOfferPlanRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $plan = $this->serviceOfferService->updatePlan($profile, $planId, $request->validated());
            return $this->successResponse(
                __('services::messages.service_offers.plan_updated_successfully'),
                $this->planToArray($plan)
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
     * Delete a plan.
     * DELETE service-offers/delete-plan/{planId}
     */
    public function destroy(int $planId)
    {
        try {
            $profile = $this->getProfile();
            $this->serviceOfferService->deletePlan($profile, $planId);
            return $this->successResponse(__('services::messages.service_offers.plan_deleted_successfully'), null);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function planToArray(ServiceOfferPlan $plan): array
    {
        return [
            'id' => $plan->id,
            'service_offer_id' => $plan->service_offer_id,
            'title' => $plan->title,
            'price' => (float) $plan->price,
            'currency' => $plan->currency,
            'estimated_delivery_time' => [
                'value' => $plan->delivery_time_value,
                'unit' => $plan->delivery_time_unit->value,
                'formatted' => $plan->delivery_time_value . ' ' . $plan->delivery_time_unit->value,
            ],
            'revision_window' => $plan->revision_window,
            'number_of_revisions' => $plan->number_of_revisions,
            'revision_scope_rules' => $plan->revision_scope_rules,
            'sort_order' => $plan->sort_order,
        ];
    }
}
