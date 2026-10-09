<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\JobApplicationInternalAttachment;

interface JobApplicationInternalAttachmentRepositoryInterface
{
    public function create(array $data): JobApplicationInternalAttachment;

    /** @return \Illuminate\Support\Collection<int, JobApplicationInternalAttachment> */
    public function getByApplicationId(int $applicationId): \Illuminate\Support\Collection;

    public function find(int $id): ?JobApplicationInternalAttachment;

    public function delete(int $id): bool;
}
