<?php

namespace Modules\Services\Repositories\Interfaces;

use Modules\Services\Models\BoostCampaignLog;

interface BoostCampaignLogRepositoryInterface
{
    public function all();

    public function findById($id);

    public function create(array $data);

    public function update(array $data, BoostCampaignLog $boostCampaignLog);

    public function delete(BoostCampaignLog $boostCampaignLog);
}
