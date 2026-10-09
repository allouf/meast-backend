<?php

namespace Modules\Advertisement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\Admin\UpdateAdvertisementStatusRequest;
use Modules\Advertisement\Http\Resources\Admin\AdvertisementResource;
use Modules\Advertisement\Services\AdvertisementService;

class AdvertisementController extends Controller
{
    public function __construct(
        protected AdvertisementService $advertisementService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only([
            'per_page', 'page', 'search', 'category_id', 'subcategory_id', 'country_code',
            'region', 'condition', 'min_price', 'max_price', 'has_images', 'is_free',
            'is_price_negotiable', 'status', 'sort'
        ]);
        $filters['for_admin'] = true;
        $ads = $this->advertisementService->getAllAdvertisements($filters);
        return $this->successResponse(__('advertisement::messages.advertisements.retrieved_successfully'), [
            'data' => AdvertisementResource::collection($ads['data']),
            'meta' => $ads['meta'],
        ]);
    }

    public function show($id)
    {
        $ad = $this->advertisementService->getAdvertisementById($id);
        if (!$ad) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.retrieved_successfully'), new AdvertisementResource($ad));
    }

    public function updateStatus(UpdateAdvertisementStatusRequest $request, $id)
    {
        try {
            $status = $request->validated()['status'];
            $advertisement = $this->advertisementService->updateAdvertisementStatus($id, $status, true);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        if ($advertisement === null) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found'), 404);
        }
        if ($advertisement === false) {
            return $this->errorResponse(__('advertisement::messages.advertisements.cannot_renew_yet'), 400);
        }
        $messages = [
            'active' => __('advertisement::messages.advertisements.activated_successfully'),
            'paused' => __('advertisement::messages.advertisements.paused_successfully'),
            'sold' => __('advertisement::messages.advertisements.marked_as_sold_successfully'),
            'draft' => __('advertisement::messages.advertisements.status_updated_successfully'),
            'expired' => __('advertisement::messages.advertisements.status_updated_successfully'),
            'archived' => __('advertisement::messages.advertisements.status_updated_successfully'),
            'rejected' => __('advertisement::messages.advertisements.rejected_successfully'),
        ];
        $message = $messages[$status] ?? __('advertisement::messages.advertisements.status_updated_successfully');
        return $this->successResponse($message, new AdvertisementResource($advertisement));
    }
}
