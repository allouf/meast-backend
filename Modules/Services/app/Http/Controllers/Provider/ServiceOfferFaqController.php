<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\AddServiceOfferFaqRequest;
use Modules\Services\Http\Requests\Provider\UpdateServiceOfferFaqRequest;
use Modules\Services\Models\ServiceOfferFaq;
use Modules\Services\Services\ServiceOfferService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferFaqController extends Controller
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
     * Add an FAQ to a service offer.
     * POST service-offers/add-faq/{id}
     */
    public function store(int $id, AddServiceOfferFaqRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $faq = $this->serviceOfferService->addFaq($profile, $id, $request->validated());
            return $this->successResponse(
                __('services::messages.service_offers.faq_created_successfully'),
                $this->faqToArray($faq),
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
     * Update an FAQ.
     * POST service-offers/update-faq/{faqId}
     */
    public function update(int $faqId, UpdateServiceOfferFaqRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $faq = $this->serviceOfferService->updateFaq($profile, $faqId, $request->validated());
            return $this->successResponse(
                __('services::messages.service_offers.faq_updated_successfully'),
                $this->faqToArray($faq)
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
     * Delete an FAQ.
     * DELETE service-offers/delete-faq/{faqId}
     */
    public function destroy(int $faqId)
    {
        try {
            $profile = $this->getProfile();
            $this->serviceOfferService->deleteFaq($profile, $faqId);
            return $this->successResponse(__('services::messages.service_offers.faq_deleted_successfully'), null);
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
    private function faqToArray(ServiceOfferFaq $faq): array
    {
        return [
            'id' => $faq->id,
            'service_offer_id' => $faq->service_offer_id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'sort_order' => $faq->sort_order,
        ];
    }
}
