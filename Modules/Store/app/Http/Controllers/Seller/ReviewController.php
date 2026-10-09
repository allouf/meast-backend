<?php

namespace Modules\Store\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\ProductReviewResource;
use Modules\Store\Services\ReviewService;
use Modules\Store\Services\StoreMemberPermissionService;
use Exception;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService,
        protected StoreMemberPermissionService $permissionService
    ) {}

    /**
     * Get reviews by store
     */
    public function getReviews(Request $request, $storeId)
    {
        try {

            $this->permissionService->requirePermission($storeId, 'store.manage');

            $filters = $request->only([
                'is_approved',
                'product_id',
                'user_id',
                'page',
                'per_page'
            ]);


            $filters['store_id'] = $storeId;


            if (isset($filters['is_approved'])) {
                $filters['is_approved'] = filter_var($filters['is_approved'], FILTER_VALIDATE_BOOLEAN);
            }

            $reviews = $this->reviewService->getAll($filters);

            return $this->successResponse('Reviews retrieved successfully', [
                'reviews' => ProductReviewResource::collection($reviews['data']),
                'meta' => $reviews['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get review details
     */
    public function show($reviewId)
    {
        try {
            $review = $this->reviewService->findById($reviewId);


            $review->load('product');


            $this->permissionService->requirePermission($review->product->store_id, 'store.manage');


            $review->load(['user', 'product']);

            return $this->successResponse(
                'Review details retrieved successfully',
                new ProductReviewResource($review)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function approve($reviewId)
    {
        try {
            $review = $this->reviewService->findById($reviewId);

            $review->load('product');

            $this->permissionService->requirePermission(
                $review->product->store_id,
                'store.manage'
            );

            $approvedReview = $this->reviewService->approve($reviewId);

            return $this->successResponse(
                'Review approved successfully',
                new ProductReviewResource($approvedReview)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
