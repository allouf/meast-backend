<?php

namespace Modules\Job\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Modules\Job\Models\JobApplicationNote;
use Modules\Job\Repositories\Interfaces\JobApplicationNoteRepositoryInterface;

class JobApplicationNoteRepository implements JobApplicationNoteRepositoryInterface
{
    public function __construct(protected JobApplicationNote $model) {}

    public function create(array $data): JobApplicationNote
    {
        return $this->model->create($data);
    }

    public function getByApplicationId(int $applicationId): Collection
    {
        return $this->model->with('user:id,name')->where('job_application_id', $applicationId)->orderByDesc('created_at')->get();
    }

    public function find(int $id): ?JobApplicationNote
    {
        return $this->model->with('jobApplication.job')->find($id);
    }

    public function delete(int $id): bool
    {
        $note = $this->model->find($id);
        return $note ? (bool) $note->delete() : false;
    }
}
