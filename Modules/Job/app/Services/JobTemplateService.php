<?php

namespace Modules\Job\Services;

use Illuminate\Support\Collection;
use Modules\Job\Repositories\Interfaces\JobTemplateRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JobTemplateService
{
    public function __construct(
        protected JobTemplateRepositoryInterface $jobTemplateRepository
    ) {}

    /**
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getMyTemplates(int $userId, array $filters = []): array|Collection
    {
        return $this->jobTemplateRepository->getByUserId($userId, $filters);
    }

    public function getTemplateForUser(int $id, int $userId): ?\Modules\Job\Models\JobTemplate
    {
        return $this->jobTemplateRepository->findForUser($id, $userId);
    }

    public function createTemplate(int $userId, array $data): \Modules\Job\Models\JobTemplate
    {
        $data['employer_id'] = $userId;
        $data['is_system_template'] = false;
        return $this->jobTemplateRepository->create($data);
    }

    public function updateTemplate(int $id, int $userId, array $data): ?\Modules\Job\Models\JobTemplate
    {
        $template = $this->jobTemplateRepository->find($id);
        if (! $template) {
            return null;
        }
        if ($template->is_system_template || $template->employer_id != $userId) {
            throw new HttpException(403, __('job::messages.job_templates.cannot_update'));
        }
        unset($data['employer_id'], $data['is_system_template']);
        return $this->jobTemplateRepository->update($id, $data);
    }

    public function deleteTemplate(int $id, int $userId): bool
    {
        $template = $this->jobTemplateRepository->find($id);
        if (! $template) {
            return false;
        }
        if ($template->is_system_template || $template->employer_id != $userId) {
            throw new HttpException(403, __('job::messages.job_templates.cannot_delete'));
        }
        return $this->jobTemplateRepository->delete($id);
    }

    /**
     * List active system templates (optional category filter).
     *
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getSystemTemplates(array $filters = []): array
    {
        return $this->jobTemplateRepository->getSystemTemplates($filters);
    }

    /**
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getAvailableByCategory(int $categoryId, int $userId, array $filters = []): array
    {
        return $this->jobTemplateRepository->getAvailableByCategory($categoryId, $userId, $filters);
    }

    /**
     * Clone a system template as the user's own template.
     */
    public function cloneFromSystem(int $systemTemplateId, int $userId): \Modules\Job\Models\JobTemplate
    {
        $template = $this->jobTemplateRepository->find($systemTemplateId);
        if (! $template) {
            throw new NotFoundHttpException(__('job::messages.job_templates.not_found'));
        }
        if (! $template->is_system_template) {
            throw new HttpException(400, __('job::messages.job_templates.not_system_template'));
        }
        $data = [
            'employer_id' => $userId,
            'category_id' => $template->category_id,
            'title' => $template->title . ' (Copy)',
            'is_system_template' => false,
            'is_active' => true,
            'fields' => $template->fields->map(fn ($f) => [
                'label' => $f->label,
                'type' => $f->type,
                'validation_rules' => $f->validation_rules,
                'options' => $f->options,
                'sort_order' => $f->sort_order,
                'is_required' => $f->is_required,
            ])->toArray(),
        ];
        return $this->jobTemplateRepository->create($data);
    }

    // --- Admin ---

    /**
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getAllTemplates(array $filters = []): array
    {
        return $this->jobTemplateRepository->all($filters);
    }

    public function getTemplateById(int $id): ?\Modules\Job\Models\JobTemplate
    {
        return $this->jobTemplateRepository->find($id);
    }

    /**
     * Admin create: is_system_template is set from admin guard (true when admin), not from request.
     */
    public function createTemplateForAdmin(array $data): \Modules\Job\Models\JobTemplate
    {
        unset($data['is_system_template']);
        $data['is_system_template'] = Auth::guard('admin')->check();
        if ($data['is_system_template']) {
            $data['employer_id'] = null;
        }
        return $this->jobTemplateRepository->create($data);
    }

    /**
     * Admin update: no ownership restriction.
     */
    public function updateTemplateForAdmin(int $id, array $data): ?\Modules\Job\Models\JobTemplate
    {
        $template = $this->jobTemplateRepository->find($id);
        if (! $template) {
            return null;
        }
        if (! empty($data['is_system_template'])) {
            $data['employer_id'] = null;
        }
        return $this->jobTemplateRepository->update($id, $data);
    }

    /**
     * Admin delete: no ownership restriction.
     */
    public function deleteTemplateForAdmin(int $id): bool
    {
        return $this->jobTemplateRepository->delete($id);
    }

    public function toggleTemplateStatus(int $id): ?\Modules\Job\Models\JobTemplate
    {
        return $this->jobTemplateRepository->toggleStatus($id);
    }
}
