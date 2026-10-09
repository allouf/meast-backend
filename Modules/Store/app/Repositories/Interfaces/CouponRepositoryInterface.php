<?php
namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\Coupon;
interface CouponRepositoryInterface
{
    public function index(array $filters = []);
    public function findById($id);
    public function create(array $data);
    public function update(array $data, Coupon $coupon);
    public function delete(Coupon $coupon);
    public function getByStoreId($storeId, ?string $search = null, ?string $status = null);
}
