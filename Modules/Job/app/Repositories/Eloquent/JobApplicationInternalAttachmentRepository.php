<?php

namespace Modules\Job\Repositories\Eloquent;

use Illuminate\Support\Collection;
use Modules\Job\Models\JobApplicationInternalAttachment;
use Modules\Job\Repositories\Interfaces\JobApplicationInternalAttachmentRepositoryInterface;

class JobApplicationInternalAttachmentRepository implements JobApplicationInternalAttachmentRepositoryInterface
{
    public function __construct(protected JobApplicationInternalAttachment $model) {}

    public function create(array $data): JobApplicationInternalAttachment
    {
        return $this->model->create($data);
    }

    public function getByApplicationId(int $applicationId): Collection
    {
        return $this->model->with('user:id,name')->where('job_application_id', $applicationId)->orderByDesc('created_at')->get();
    }

    public function find(int $id): ?JobApplicationInternalAttachment
    {
        return $this->model->with('jobApplication.job')->find($id);
    }

    public function delete(int $id): bool
    {
        $attachment = $this->model->find($id);
        return $attachment ? (bool) $attachment->delete() : false;
    }
}
