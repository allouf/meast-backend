<?php

namespace Modules\Services\Repositories\Eloquent;

use Modules\Services\Models\BoostCampaignLog;
use Modules\Services\Repositories\Interfaces\BoostCampaignLogRepositoryInterface;

class BoostCampaignLogRepository implements BoostCampaignLogRepositoryInterface
{
    protected $model;

    public function __construct(BoostCampaignLog $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, BoostCampaignLog $boostCampaignLog)
    {
        $boostCampaignLog->update($data);
        return $boostCampaignLog;
    }

    public function delete(BoostCampaignLog $boostCampaignLog)
    {
        return $boostCampaignLog->delete();
    }
}
