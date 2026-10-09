<?php

namespace Modules\Job\Services;

use Illuminate\Support\Collection;
use Modules\Job\Repositories\Interfaces\JobCategoryRepositoryInterface;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JobCategoryService
{
    public function __construct(
        protected JobCategoryRepositoryInterface $jobCategoryRepository
    ) {}

    public function getAllCategories(array $filters = []): Collection|array
    {
        return $this->jobCategoryRepository->all($filters);
    }

    public function getCategoryById(int $id): ?\Modules\Job\Models\JobCategory
    {
        return $this->jobCategoryRepository->find($id);
    }

    public function getCategoryBySlug(string $slug): ?\Modules\Job\Models\JobCategory
    {
        return $this->jobCategoryRepository->findBySlug($slug);
    }

    public function createCategory(array $data): \Modules\Job\Models\JobCategory
    {
        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->jobCategoryRepository->create($data);
    }

    public function updateCategory(int $id, array $data): ?\Modules\Job\Models\JobCategory
    {
        $category = $this->jobCategoryRepository->find($id);
        if (! $category) {
            return null;
        }
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        return $this->jobCategoryRepository->update($id, $data);
    }

    public function deleteCategory(int $id): void
    {
        $category = $this->jobCategoryRepository->find($id);
        if (! $category) {
            throw new NotFoundHttpException(__('job::messages.job_categories.not_found'));
        }
        if ($this->jobCategoryRepository->hasJobs($id)) {
            throw new HttpException(409, __('job::messages.job_categories.cannot_delete_has_jobs'));
        }
        $this->jobCategoryRepository->delete($id);
    }

    public function toggleCategoryStatus(int $id): ?\Modules\Job\Models\JobCategory
    {
        $category = $this->jobCategoryRepository->toggleStatus($id);
        if (! $category) {
            throw new NotFoundHttpException(__('job::messages.job_categories.not_found'));
        }
        return $category;
    }
}
