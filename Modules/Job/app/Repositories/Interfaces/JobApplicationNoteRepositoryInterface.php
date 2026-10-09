<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\JobApplicationNote;

interface JobApplicationNoteRepositoryInterface
{
    public function create(array $data): JobApplicationNote;

    /** @return \Illuminate\Support\Collection<int, JobApplicationNote> */
    public function getByApplicationId(int $applicationId): \Illuminate\Support\Collection;

    public function find(int $id): ?JobApplicationNote;

    public function delete(int $id): bool;
}
