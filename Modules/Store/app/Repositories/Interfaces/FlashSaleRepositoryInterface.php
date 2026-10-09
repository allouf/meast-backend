<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\FlashSale;
interface FlashSaleRepositoryInterface
{
    public function all(array $filters = []);

    public function findById($id);

    public function create(array $data);

    public function update(array $data, FlashSale $flashSale);

    public function delete(FlashSale $flashSale);

    public function getByStoreId(int $storeId, $paginate = true, ?string $search = null,array $filters = []);

    public function hasAvailableStock(FlashSale $flashSale): bool;

    public function loadVariantsWithStock(FlashSale $flashSale): FlashSale;

}
