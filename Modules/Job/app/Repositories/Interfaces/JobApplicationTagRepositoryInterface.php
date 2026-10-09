<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\JobApplicationTag;

interface JobApplicationTagRepositoryInterface
{
    public function createForJob(int $jobId, string $name, string $color): JobApplicationTag;

    public function update(JobApplicationTag $tag, string $name, string $color): JobApplicationTag;

    /** @return \Illuminate\Support\Collection<int, JobApplicationTag> */
    public function getByJobId(int $jobId): \Illuminate\Support\Collection;

    public function findById(int $id): ?JobApplicationTag;

    public function attachToApplication(int $applicationId, int $tagId): void;

    public function detachFromApplication(int $applicationId, int $tagId): void;

    /** @return \Illuminate\Support\Collection<int, JobApplicationTag> */
    public function getTagsForApplication(int $applicationId): \Illuminate\Support\Collection;

    public function findByNameAndJob(string $name, int $jobId): ?JobApplicationTag;
}
