<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\ProductReviewResource;
use Modules\Store\Services\ReviewService;
use Exception;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewService $reviewService
    ) {}

    /**
     * Get all reviews with pagination and filters
     */
    public function getAll(Request $request)
    {
        try {
            $filters = $request->only([
                'is_approved',
                'rating',
                'product_id',
                'user_id',
                'store_id',
                'from_date',
                'to_date',
                'search',
                'page',
                'per_page'
            ]);

            // Convert is_approved string to boolean if provided
            if (isset($filters['is_approved'])) {
                $filters['is_approved'] = filter_var($filters['is_approved'], FILTER_VALIDATE_BOOLEAN);
            }

            $reviews = $this->reviewService->getAll($filters);

            return $this->successResponse(__('store::messages.admin.reviews_retrieved_successfully'), [
                'reviews' => ProductReviewResource::collection($reviews['data']),
                'meta' => $reviews['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show($reviewId)
    {
        try {
            $review = $this->reviewService->findById($reviewId);


            $review->load('product');


            $review->load(['user', 'product']);

            return $this->successResponse(
                'Review details retrieved successfully',
                new ProductReviewResource($review)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    /**
     * Approve a review
     */
    public function approve($reviewId)
    {
        try {
            $review = $this->reviewService->approve($reviewId);
            return $this->successResponse(__('store::messages.admin.review_approved_successfully'), new ProductReviewResource($review));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
