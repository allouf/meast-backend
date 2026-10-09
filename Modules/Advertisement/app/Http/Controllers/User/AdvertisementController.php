<?php

namespace Modules\Advertisement\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Advertisement\Http\Requests\User\AdvertisementRequest;
use Modules\Advertisement\Http\Requests\User\UpdateAdvertisementRequest;
use Modules\Advertisement\Http\Requests\User\UpdateAdvertisementStatusRequest;
use Modules\Advertisement\Http\Resources\User\AdvertisementResource;
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
            'is_price_negotiable', 'sort', 'status'
        ]);
        $advertisements = $this->advertisementService->getAllAdvertisements($filters);

        if (is_array($advertisements) && isset($advertisements['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisements.retrieved_successfully'), [
                'data' => AdvertisementResource::collection($advertisements['data']),
                'meta' => $advertisements['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.retrieved_successfully'), AdvertisementResource::collection($advertisements));
    }

    public function show($id)
    {
        $advertisement = $this->advertisementService->getAdvertisementBySlug($id);
        if (!$advertisement) {
            $advertisement = $this->advertisementService->getAdvertisementById($id);
        }
        if (!$advertisement) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found'), 404);
        }
        $advertisement = $this->advertisementService->viewAdvertisement($advertisement->id);
        return $this->successResponse(__('advertisement::messages.advertisements.retrieved_successfully'), new AdvertisementResource($advertisement));
    }

    public function store(AdvertisementRequest $request)
    {
        try {
            $advertisement = $this->advertisementService->createAdvertisement($request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.created_successfully'), new AdvertisementResource($advertisement), 201);
    }

    public function update(UpdateAdvertisementRequest $request, $id)
    {
        try {
            $advertisement = $this->advertisementService->updateAdvertisement($id, $request->validated());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        if (!$advertisement) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found_or_unauthorized'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.updated_successfully'), new AdvertisementResource($advertisement));
    }

    public function destroy($id)
    {
        $deleted = $this->advertisementService->deleteAdvertisement($id);
        if (!$deleted) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found_or_unauthorized'), 404);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.deleted_successfully'), null);
    }

    public function myAdvertisements(Request $request)
    {
        $filters = $request->only([
            'per_page', 'page', 'search', 'category_id', 'subcategory_id', 'country_code',
            'region', 'condition', 'min_price', 'max_price', 'has_images', 'is_free',
            'is_price_negotiable', 'sort', 'status',
        ]);
        $advertisements = $this->advertisementService->getMyAdvertisements($filters);
        if (is_array($advertisements) && isset($advertisements['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisements.my_advertisements_retrieved_successfully'), [
                'data' => AdvertisementResource::collection($advertisements['data']),
                'meta' => $advertisements['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.my_advertisements_retrieved_successfully'), AdvertisementResource::collection($advertisements));
    }

    public function search(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);
        $query = $request->get('q');
        $filters = $request->only(['per_page', 'category_id']);
        $advertisements = $this->advertisementService->searchAdvertisements($query, $filters);
        if (is_array($advertisements) && isset($advertisements['data'])) {
            return $this->successResponse(__('advertisement::messages.advertisements.search_results_retrieved_successfully'), [
                'data' => AdvertisementResource::collection($advertisements['data']),
                'meta' => $advertisements['meta']
            ]);
        }
        return $this->successResponse(__('advertisement::messages.advertisements.search_results_retrieved_successfully'), AdvertisementResource::collection($advertisements));
    }

    public function similar($id)
    {
        $advertisement = $this->advertisementService->getAdvertisementById($id);
        if (!$advertisement) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found'), 404);
        }
        $similar = $this->advertisementService->getSimilarAdvertisements($id, $advertisement->advertisement_category_id, 10);
        return $this->successResponse(__('advertisement::messages.advertisements.similar_retrieved_successfully'), AdvertisementResource::collection($similar));
    }

    public function updateStatus(UpdateAdvertisementStatusRequest $request, $id)
    {
        try {
            $status = $request->validated()['status'];
            $advertisement = $this->advertisementService->updateAdvertisementStatus($id, $status);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
        if ($advertisement === null) {
            return $this->errorResponse(__('advertisement::messages.advertisements.not_found_or_unauthorized'), 404);
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
            'rejected' => __('advertisement::messages.advertisements.status_updated_successfully'),
        ];
        $message = $messages[$status] ?? __('advertisement::messages.advertisements.status_updated_successfully');
        return $this->successResponse($message, new AdvertisementResource($advertisement));
    }
}
