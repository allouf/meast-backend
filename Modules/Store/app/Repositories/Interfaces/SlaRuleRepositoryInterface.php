<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\SlaRule;
interface SlaRuleRepositoryInterface
{
    public function all();

    public function findById($id);

    public function create(array $data);

    public function update(array $data, SlaRule $slaRule);

    public function delete(SlaRule $slaRule);

    public function getByStore($storeId);

    public function getByStoreAndIssueType($storeId, $issueTypeId);
}
