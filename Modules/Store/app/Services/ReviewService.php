<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\ReviewRepositoryInterface;
use Modules\Store\Models\ProductReviewImage;
use Modules\Store\Models\StoreMember;
use Modules\Store\Enums\StoreMemberRole;
use App\Utilities\FileManager;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewService
{
    protected $reviewRepository;

    public function __construct(ReviewRepositoryInterface $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function create(array $data)
    {
        $images = $data['images'] ?? [];
        unset($data['images']);

        $review = $this->reviewRepository->create($data);

        if (!empty($images)) {
            $this->createReviewImages($review->id, $images);
        }

        return $review->load('images');
    }

    public function getProductReviews($productId)
    {
        return $this->reviewRepository->getProductReviews($productId);
    }

    public function findById($reviewId)
    {
        return $this->reviewRepository->findById($reviewId);
    }

    public function update(array $data, $reviewId)
    {
        $review = $this->reviewRepository->findById($reviewId);

        $images = $data['images'] ?? null;
        unset($data['images']);

        $updatedReview = $this->reviewRepository->update($data, $review);

        if ($images !== null) {
            $this->deleteReviewImages($review->id);
            if (!empty($images)) {
                $this->createReviewImages($review->id, $images);
            }
        }

        return $updatedReview->load('images');
    }

    public function delete($reviewId)
    {
        $review = $this->reviewRepository->findById($reviewId);

        if ($review && $review->images) {
            foreach ($review->images as $image) {
                FileManager::delete($image->file_path);
            }
        }

        return $this->reviewRepository->delete($review);
    }

    /**
     * Create review images
     */
    private function createReviewImages(int $reviewId, array $images): void
    {
        $imageData = [];
        $sortOrder = 0;

        foreach ($images as $image) {
            if ($image) {
                $filePath = FileManager::upload('reviews', $image);
                $imageData[] = [
                    'review_id' => $reviewId,
                    'file_path' => $filePath,
                    'sort_order' => $sortOrder++,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($imageData)) {
            ProductReviewImage::insert($imageData);
        }
    }

    /**
     * Delete all review images
     */
    private function deleteReviewImages(int $reviewId): void
    {
        $images = ProductReviewImage::where('review_id', $reviewId)->get();
        foreach ($images as $image) {
            FileManager::delete($image->file_path);
        }
        ProductReviewImage::where('review_id', $reviewId)->delete();
    }

    /**
     * Get all reviews with filters (for admin)
     */
    public function getAll(array $filters = [])
    {
        return $this->reviewRepository->getAll($filters);
    }

    /**
     * Approve a review
     */
    public function approve($reviewId)
    {
        $review = $this->reviewRepository->findById($reviewId);
        return $this->reviewRepository->approve($review);
    }
    public function getReviewStatisticsByStore(int $storeId, $userId)
    {
        $storeOwner = StoreMember::where('store_id', $storeId)
            ->where('user_id', $userId)
            ->where('role', StoreMemberRole::STORE_OWNER)
            ->where('status', 'active')
            ->first();

        if (!$storeOwner) {
            throw new HttpException(403, 'You do not have permission to view statistics for this store. Only store owners can view statistics.');
        }

        return $this->reviewRepository->getReviewStatisticsByStore($storeId);
    }
}
