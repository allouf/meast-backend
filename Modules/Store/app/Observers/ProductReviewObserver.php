<?php

namespace Modules\Store\Observers;

use Modules\Store\Models\Product;
use Modules\Store\Models\ProductReview;

class ProductReviewObserver
{
    public function created(ProductReview $productReview): void
    {
        $this->updateProductRating($productReview->product_id);
    }

    public function updated(ProductReview $productReview): void
    {
        if ($productReview->wasChanged(['rating', 'is_approved'])) {
            $this->updateProductRating($productReview->product_id);
        }
    }

    public function deleted(ProductReview $productReview): void
    {
        $this->updateProductRating($productReview->product_id);
    }

    public function restored(ProductReview $productReview): void
    {
        $this->updateProductRating($productReview->product_id);
    }

    public function forceDeleted(ProductReview $productReview): void
    {
        $this->updateProductRating($productReview->product_id);
    }

    private function updateProductRating(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product) {
            return;
        }
        $approvedReviews = ProductReview::where('product_id', $productId)
            ->where('is_approved', true)
            ->get();

        $ratingCount = $approvedReviews->count();

        if ($approvedReviews->isEmpty()) {
            $avgRating = 0;
        } else {
            $avgRating = $approvedReviews->avg('rating');
        }

        $product->update([
            'avg_rating' => round($avgRating, 2),
            'rating_count' => $ratingCount
        ]);
    }
}
