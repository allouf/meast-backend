<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Store\Models\ProductReview;
use Modules\Store\Repositories\Interfaces\ReviewRepositoryInterface;
use Carbon\Carbon;

class ReviewRepository implements ReviewRepositoryInterface
{
    use PaginateTrait;

    public function __construct(
        protected ProductReview $model
    ) {}

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function getProductReviews($productId)
    {
        $query = $this->model->with(['user', 'images'])
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc');
        return $this->paginate($query);
    }

    public function findById($reviewId)
    {
        return $this->model->with('images')->findOrFail($reviewId);
    }

    public function update(array $data, ProductReview $review)
    {
        $review->update($data);
        return $review;
    }

    public function delete(ProductReview $review)
    {
        return $review->delete();
    }

    public function getAll(array $filters)
    {
        $query = $this->model->with(['user', 'product.gallery', 'images'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['is_approved'])) {
            $query->where('is_approved', filter_var($filters['is_approved'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['store_id'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('store_id', $filters['store_id']);
            });
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'LIKE', "%{$search}%");
                })
                    ->orWhereHas('product', function ($p) use ($search) {
                        $p->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        return $this->paginate($query);
    }

    public function approve(ProductReview $review)
    {
        $review->update(['is_approved' => true]);
        return $review->fresh(['user', 'product', 'images']);
    }

    public function getReviewStatisticsByStore(int $storeId)
    {
        $newReviewsFrom = Carbon::now()->subDays(7);

        return ProductReview::whereHas('product', function ($query) use ($storeId) {
            $query->where('store_id', $storeId);
        })
            ->where('is_approved', true)
            ->selectRaw('
        COUNT(*) as all_reviews_count,
        SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_reviews_count,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as highest_rated_count,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as lowest_rated_count
    ', [$newReviewsFrom])
            ->first();
    }
}
