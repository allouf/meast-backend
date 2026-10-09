<?php

namespace Modules\Job\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Modules\Job\Models\JobApplicationTag;
use Modules\Job\Repositories\Interfaces\JobApplicationTagRepositoryInterface;

class JobApplicationTagRepository implements JobApplicationTagRepositoryInterface
{
    public function __construct(protected JobApplicationTag $model) {}

    public function createForJob(int $jobId, string $name, string $color): JobApplicationTag
    {
        return $this->model->create(['job_id' => $jobId, 'name' => $name, 'color' => $color]);
    }

    public function update(JobApplicationTag $tag, string $name, string $color): JobApplicationTag
    {
        $tag->update([
            'name'  => $name,
            'color' => $color,
        ]);

        return $tag->fresh();
    }

    public function getByJobId(int $jobId): Collection
    {
        return $this->model->where('job_id', $jobId)->orderBy('name')->get();
    }

    public function findById(int $id): ?JobApplicationTag
    {
        return $this->model->find($id);
    }

    public function attachToApplication(int $applicationId, int $tagId): void
    {
        $tag = $this->model->find($tagId);
        if ($tag) $tag->jobApplications()->syncWithoutDetaching([$applicationId]);
    }

    public function detachFromApplication(int $applicationId, int $tagId): void
    {
        $tag = $this->model->find($tagId);
        if ($tag) $tag->jobApplications()->detach($applicationId);
    }

    public function getTagsForApplication(int $applicationId): Collection
    {
        return $this->model->whereHas('jobApplications', fn($q) => $q->where('job_applications.id', $applicationId))->get();
    }

    public function findByNameAndJob(string $name, int $jobId): ?JobApplicationTag
    {
        return $this->model->where('job_id', $jobId)->where('name', $name)->first();
    }
}
