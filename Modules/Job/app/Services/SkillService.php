<?php

namespace Modules\Job\Services;

use Illuminate\Support\Str;
use Modules\Job\Repositories\Interfaces\SkillRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SkillService
{
    public function __construct(
        protected SkillRepositoryInterface $skillRepository
    ) {}

    public function getAll(array $filters = []): array
    {
        return $this->skillRepository->all($filters);
    }

    public function getById(int $id): ?\Modules\Job\Models\Skill
    {
        return $this->skillRepository->find($id);
    }

    public function getBySlug(string $slug): ?\Modules\Job\Models\Skill
    {
        return $this->skillRepository->findBySlug($slug);
    }

    public function create(array $data): \Modules\Job\Models\Skill
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->skillRepository->create($data);
    }

    public function update(int $id, array $data): ?\Modules\Job\Models\Skill
    {
        $skill = $this->skillRepository->find($id);
        if (! $skill) {
            return null;
        }
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->skillRepository->update($id, $data);
    }

    public function delete(int $id): void
    {
        $skill = $this->skillRepository->find($id);
        if (! $skill) {
            throw new NotFoundHttpException(__('job::messages.skills.not_found'));
        }
        $this->skillRepository->delete($id);
    }

    public function toggleStatus(int $id): ?\Modules\Job\Models\Skill
    {
        $skill = $this->skillRepository->toggleStatus($id);
        if (! $skill) {
            throw new NotFoundHttpException(__('job::messages.skills.not_found'));
        }
        return $skill;
    }
}
