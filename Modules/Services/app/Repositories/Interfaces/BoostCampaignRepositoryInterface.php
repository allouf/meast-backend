<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Services\Models\BoostCampaign;

interface BoostCampaignRepositoryInterface
{
    public function all();

    public function findById($id);

    /** Admin detail: nested relations & recent audit logs. */
    public function findByIdWithAdminRelations(int $id): BoostCampaign;

    public function create(array $data);

    public function update(array $data, BoostCampaign $boostCampaign);

    public function delete(BoostCampaign $boostCampaign);

    public function paginatedForProvider(int $providerId, array $filters = []): LengthAwarePaginator;

    /** Admin console: boosts with offer/provider/plan loaded. */
    public function paginatedForAdmin(array $filters = []): LengthAwarePaginator;

    public function lockActiveWindowForOfferAndProvider(int $serviceOfferId, int $providerId): ?BoostCampaign;

    /**
     * Active boost rows for an offer, locked for update (moderation refund).
     */
    public function lockActiveCampaignsForServiceOffer(int $serviceOfferId): Collection;
}
