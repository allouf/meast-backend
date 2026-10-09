<?php

namespace Modules\Job\Services;

use Modules\Job\Models\JobApplication;
use Modules\Job\Repositories\Interfaces\JobApplicationInternalAttachmentRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationNoteRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationTagRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobApplicationInternalService
{
    public const INTERNAL_ATTACHMENTS_DIR = 'job_applications/internal_attachments';

    public const ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function __construct(
        protected JobApplicationRepositoryInterface $jobApplicationRepository,
        protected JobApplicationNoteRepositoryInterface $noteRepository,
        protected JobApplicationTagRepositoryInterface $tagRepository,
        protected JobApplicationInternalAttachmentRepositoryInterface $attachmentRepository,
        protected JobRepositoryInterface $jobRepository
    ) {}

    /**
     * Ensure application exists and its job belongs to employer. Throws otherwise.
     */
    public function ensureApplicationBelongsToEmployer(int $applicationId, int $employerId): JobApplication
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job']);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        if ((int) $application->job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }
        return $application;
    }

    /**
     * Ensure application exists and belongs to the given candidate. Throws otherwise.
     */
    public function ensureApplicationBelongsToCandidate(int $applicationId, int $candidateId): JobApplication
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job']);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        if ((int) $application->candidate_id !== $candidateId) {
            throw new HttpException(403, __('job::messages.job_applications.not_found'));
        }
        return $application;
    }

    /**
     * Add internal note (employer). user_id on the note is the application's candidate_id, not the employer.
     */
    public function addNote(int $applicationId, int $employerId, string $content): \Modules\Job\Models\JobApplicationNote
    {
        $application = $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);
        $note = $this->noteRepository->create([
            'job_application_id' => $applicationId,
            'user_id' => $application->candidate_id,
            'content' => $content,
        ]);
        return $note->load('user:id,name');
    }

    /**
     * List internal notes for an application (employer only).
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Job\Models\JobApplicationNote>
     */
    public function getNotes(int $applicationId, int $employerId): \Illuminate\Support\Collection
    {
        $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);
        return $this->noteRepository->getByApplicationId($applicationId);
    }

    /**
     * Create a tag for a specific job (owned by employer).
     */
    public function createTag(int $jobId, int $employerId, string $name, string $color): \Modules\Job\Models\JobApplicationTag
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job || (int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }

        $existing = $this->tagRepository->findByNameAndJob($name, $jobId);
        if ($existing) {
            throw new HttpException(422, __('job::messages.job_applications.tag_already_exists'));
        }
        return $this->tagRepository->createForJob($jobId, $name, $color);
    }

    public function updateTag(int $tagId, int $employerId, string $name, string $color): \Modules\Job\Models\JobApplicationTag
    {
        $tag = $this->tagRepository->findById($tagId);

        if (! $tag) {
            throw new HttpException(404, __('job::messages.job_applications.tag_not_found'));
        }

        if ((int) $tag->job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }

        $existing = $this->tagRepository->findByNameAndJob($name, $tag->job_id);

        if ($existing && $existing->id !== $tagId) {
            throw new HttpException(422, __('job::messages.job_applications.tag_already_exists'));
        }

        return $this->tagRepository->update($tag, $name, $color);
    }

    /**
     * List tags for a job (employer only).
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Job\Models\JobApplicationTag>
     */
    public function getEmployerTags(int $jobId, int $employerId): \Illuminate\Support\Collection
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job || (int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }

        return $this->tagRepository->getByJobId($jobId);
    }

    /**
     * Attach tag to application. Authorized by employer (auth user).
     */
    public function attachTag(int $applicationId, int $tagId, int $employerId): void
    {
        $application = $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);
        $tag = $this->tagRepository->findById($tagId);
        if (! $tag) {
            throw new HttpException(404, __('job::messages.job_applications.tag_not_found'));
        }
        if ((int) $tag->job_id !== (int) $application->job_id) {
            throw new HttpException(422, __('job::messages.job_applications.tag_not_for_job'));
        }
        $this->tagRepository->attachToApplication($applicationId, $tagId);
    }

    /**
     * Detach tag from application. Authorized by employer (auth user).
     */
    public function detachTag(int $applicationId, int $tagId, int $employerId): void
    {
        $application = $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);
        $tag = $this->tagRepository->findById($tagId);
        if (! $tag) {
            throw new HttpException(404, __('job::messages.job_applications.tag_not_found'));
        }
        if ((int) $tag->job_id !== (int) $application->job_id) {
            throw new HttpException(422, __('job::messages.job_applications.tag_not_for_job'));
        }
        $this->tagRepository->detachFromApplication($applicationId, $tagId);
    }

    /**
     * Get tags attached to an application. Authorized by employer (auth user).
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Job\Models\JobApplicationTag>
     */
    public function getTagsForApplication(int $applicationId, int $employerId): \Illuminate\Support\Collection
    {
        $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);
        return $this->tagRepository->getTagsForApplication($applicationId);
    }

    /**
     * Upload internal attachment (PDF/DOC). Authorized by employer (auth user). user_id on attachment = uploader.
     */
    public function uploadAttachment(int $applicationId, int $employerId, UploadedFile $file): \Modules\Job\Models\JobApplicationInternalAttachment
    {
        $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);

        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new HttpException(422, __('job::messages.job_applications.attachment_invalid_type'));
        }

        $dir = self::INTERNAL_ATTACHMENTS_DIR . '/' . $applicationId;
        $disk = Storage::disk(config('filesystems.default'));
        if (! $disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }
        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9-_.]/', '_', $file->getClientOriginalName());
        $path = $file->storeAs($dir, $fileName, config('filesystems.default'));

        $attachment = $this->attachmentRepository->create([
            'job_application_id' => $applicationId,
            'user_id' => $employerId,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'size' => $file->getSize(),
        ]);

        return $attachment->load('user:id,name');
    }

    /**
     * List internal attachments for an application. Authorized by employer (auth user).
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Job\Models\JobApplicationInternalAttachment>
     */
    public function getAttachments(int $applicationId, int $employerId): \Illuminate\Support\Collection
    {
        $this->ensureApplicationBelongsToEmployer($applicationId, $employerId);
        return $this->attachmentRepository->getByApplicationId($applicationId);
    }

    /**
     * Delete internal attachment and its file. Authorized by employer (auth user).
     */
    public function deleteAttachment(int $attachmentId, int $employerId): void
    {
        $attachment = $this->attachmentRepository->find($attachmentId);
        if (! $attachment) {
            throw new HttpException(404, __('job::messages.job_applications.attachment_not_found'));
        }
        $this->ensureApplicationBelongsToEmployer($attachment->job_application_id, $employerId);

        $disk = Storage::disk(config('filesystems.default'));
        if ($disk->exists($attachment->file_path)) {
            $disk->delete($attachment->file_path);
        }
        $this->attachmentRepository->delete($attachmentId);
    }

    /**
     * Get attachment for download. Authorized by employer (auth user).
     */
    public function getAttachmentForDownload(int $attachmentId, int $employerId): ?\Modules\Job\Models\JobApplicationInternalAttachment
    {
        $attachment = $this->attachmentRepository->find($attachmentId);
        if (! $attachment) {
            return null;
        }
        $this->ensureApplicationBelongsToEmployer($attachment->job_application_id, $employerId);
        return $attachment;
    }
}
