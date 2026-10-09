<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\UpdateAllServiceOfferAvailableSlotsRequest;
use Modules\Services\Models\ServiceOfferAvailableSlot;
use Modules\Services\Services\ServiceOfferService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferAvailableSlotController extends Controller
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
     * Replace all available slots for a service offer.
     * POST service-offers/{serviceId}/update-slots
     * Body: [ { "day", "start_time", "end_time" }, ... ] or { "slots": [ ... ] }
     */
    public function updateAllSlots(int $serviceId, UpdateAllServiceOfferAvailableSlotsRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $slots = $this->serviceOfferService->updateAllAvailableSlots($profile, $serviceId, $request->getSlots());
            return $this->successResponse(
                __('services::messages.service_offers.available_slots_updated_successfully'),
                $slots->map(fn ($slot) => $this->slotToArray($slot))->values()->all()
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
     * @return array<string, mixed>
     */
    private function slotToArray(ServiceOfferAvailableSlot $slot): array
    {
        return [
            'id' => $slot->id,
            'service_offer_id' => $slot->service_offer_id,
            'day' => $slot->day,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'sort_order' => $slot->sort_order,
        ];
    }
}
