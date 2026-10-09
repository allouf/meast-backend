<?php

namespace Modules\Store\Repositories\Eloquent;

use Modules\Store\Models\SlaRule;
use Modules\Store\Repositories\Interfaces\SlaRuleRepositoryInterface;

class SlaRuleRepository implements SlaRuleRepositoryInterface
{
    protected $model;

    public function __construct(SlaRule $model)
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

    public function update(array $data, SlaRule $slaRule)
    {
        $slaRule->update($data);
        return $slaRule;
    }

    public function delete(SlaRule $slaRule)
    {
        return $slaRule->delete();
    }

    public function getByStore($storeId)
    {
        return $this->model
            ->with('issueType')
            ->where('store_id', $storeId)
            ->get();
    }

    public function getByStoreAndIssueType($storeId, $issueTypeId)
    {
        return $this->model
            ->where('store_id', $storeId)
            ->where('issue_type_id', $issueTypeId)
            ->first();
    }
}
