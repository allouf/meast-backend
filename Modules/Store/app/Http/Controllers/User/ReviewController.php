<?php

namespace Modules\Store\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Requests\User\ProductReviewRequest;
use Modules\Store\Http\Resources\User\ProductReviewResource;
use Modules\Store\Services\ReviewService;
use Exception;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    public function getReviews($productId)
    {
        try {
            $reviews = $this->reviewService->getProductReviews($productId);
            return $this->successResponse('success', [
                'reviews' => ProductReviewResource::collection($reviews['data']),
                'meta' => $reviews['meta']
            ]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function createReview(ProductReviewRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::guard('api')->id();

        try {
            $review = $this->reviewService->create($data);
            return $this->successResponse('Review created successfully', new ProductReviewResource($review));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
    public function updateReview(ProductReviewRequest $request, $reviewId)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::guard('api')->id();

        try {
            $review = $this->reviewService->update($data, $reviewId);
            return $this->successResponse('Review updated successfully', new ProductReviewResource($review));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteReview($reviewId)
    {
        try {
            $this->reviewService->delete($reviewId);
            return $this->successResponse('Review deleted successfully');
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
