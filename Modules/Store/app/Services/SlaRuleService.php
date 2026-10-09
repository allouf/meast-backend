<?php

namespace Modules\Store\Services;

use Modules\Store\Repositories\Interfaces\SlaRuleRepositoryInterface;
use Modules\Store\Services\StoreMemberPermissionService;
use \Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SlaRuleService
{
    protected $slaRuleRepository;
    protected $permissionService;
    public function __construct(
        SlaRuleRepositoryInterface $slaRuleRepository,
        StoreMemberPermissionService $permissionService
    ) {
        $this->slaRuleRepository = $slaRuleRepository;
        $this->permissionService = $permissionService;
    }
    public function getStoreSlaRules(int $storeId)
    {
        $this->permissionService->requirePermission($storeId, 'policies.manage');
        return $this->slaRuleRepository->getByStore($storeId);
    }
    public function findById(int $id)
    {
        $rule = $this->slaRuleRepository->findById($id);
        if (!$rule) {
            throw new ModelNotFoundException("SLA Rule not found.");
        }
        $this->permissionService->requirePermission($rule->store_id, 'policies.manage');
        return $rule;
    }
    public function create($storeId, array $data)
    {
        $this->permissionService->requirePermission($storeId, 'policies.manage');
        $data['store_id'] = $storeId;
        $existing = $this->slaRuleRepository->getByStoreAndIssueType(
            $storeId,
            $data['issue_type_id']
        );
        if ($existing) {
            throw new HttpException(400, "SLA Rule for this Issue Type already exists for this store.");
        }
        return $this->slaRuleRepository->create($data);
    }
    public function update(int $id, array $data)
    {
        $rule = $this->slaRuleRepository->findById($id);
        if (!$rule) {
            throw new ModelNotFoundException("SLA Rule not found.");
        }
        $this->permissionService->requirePermission($rule->store_id, 'policies.manage');
        return $this->slaRuleRepository->update($data, $rule);
    }
    public function delete(int $id)
    {
        $rule = $this->slaRuleRepository->findById($id);
        if (!$rule) {
            throw new ModelNotFoundException("SLA Rule not found.");
        }
        $this->permissionService->requirePermission($rule->store_id, 'policies.manage');
        return $this->slaRuleRepository->delete($rule);
    }
}
