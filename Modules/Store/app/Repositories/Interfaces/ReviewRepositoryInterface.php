<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\ProductReview;

interface ReviewRepositoryInterface
{
    public function create(array $data);
    public function getProductReviews($productId);
    public function findById($reviewId);
    public function update(array $data, ProductReview $review);
    public function delete(ProductReview $review);
    public function getAll(array $filters);
    public function approve(ProductReview $review);
    public function getReviewStatisticsByStore(int $storeId);
}
