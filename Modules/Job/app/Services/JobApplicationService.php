<?php

namespace Modules\Job\Services;

use Modules\Core\Models\User;
use Modules\Core\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\EmailService;
use App\Services\FileThreatScanService;
use App\Utilities\FileManager;
use Modules\Job\Enums\ApplicationStatus;
use Modules\Job\Models\Job;
use Modules\Job\Models\JobApplication;
use Modules\Job\Models\JobApplicationFieldAnswer;
use Modules\Job\Repositories\Interfaces\JobApplicationNoteRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobApplicationRepositoryInterface;
use Modules\Job\Repositories\Interfaces\JobRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobApplicationService
{
    public const RESUME_DIR = 'job_applications';

    public function __construct(
        protected JobApplicationRepositoryInterface $jobApplicationRepository,
        protected JobRepositoryInterface $jobRepository,
        protected UserRepositoryInterface $userRepository,
        protected JobApplicationNoteRepositoryInterface $noteRepository,
        protected FileThreatScanService $fileThreatScanService,
        protected EmailService $emailService
    ) {}

    /**
     * Submit a job application: duplicate check, file scan, template field answers, status transition, notifications.
     *
     * @param  array<int, array{field_id: int, value: mixed}>  $answers  Optional answers for job template fields.
     * @throws HttpException When duplicate application, job not found, or invalid answers
     */
    public function submit(int $candidateId, int $jobId, UploadedFile $resume, ?string $coverLetter = null, array $answers = []): JobApplication
    {
        try {
            $candidate = $this->userRepository->findById($candidateId);
        } catch (ModelNotFoundException) {
            throw new HttpException(404, __('job::messages.job_applications.candidate_not_found'));
        }

        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new HttpException(404, __('job::messages.jobs.not_found'));
        }

        $job->loadMissing('jobTemplate.fields');
        $this->validateTemplateAnswers($job, $answers);

        $this->ensureNotDuplicate($candidateId, $jobId);
        $resumePath = FileManager::upload(self::RESUME_DIR, $resume);

        $application = DB::transaction(function () use ($candidateId, $jobId, $resumePath, $coverLetter, $answers) {
            $app = $this->jobApplicationRepository->create([
                'candidate_id' => $candidateId,
                'job_id' => $jobId,
                'status' => ApplicationStatus::New,
                'resume_path' => $resumePath,
                'cover_letter' => $coverLetter,
            ]);
            $this->saveFieldAnswers($app->id, $answers);
            return $app;
        });

        $scanPath = $resume->getRealPath();
        $scanResult = $this->fileThreatScanService->scanFileForThreats($scanPath, $resume);

        if ($scanResult['safe']) {
            $this->jobApplicationRepository->update($application->id, ['status' => ApplicationStatus::New]);
            $this->sendSuccessNotification($candidate, $application, $job);
        } else {
            $reason = $scanResult['reason'] ?? __('job::messages.job_applications.scan_failed');
            $this->jobApplicationRepository->update($application->id, [
                'status' => ApplicationStatus::Rejected,
                'rejection_reason' => $reason,
            ]);
            $this->sendRejectionNotification($candidate, $application, $reason, $job);
        }

        return $this->jobApplicationRepository->findWithRelations($application->id, ['job', 'candidate', 'fieldAnswers.jobTemplateField', 'notes.user'])
            ?? $application;
    }

    /**
     * Validate that answers reference template fields for this job and required fields are filled.
     *
     * @param  array<int, array{field_id: int, value: mixed}>  $answers
     * @throws HttpException When a field_id is invalid or a required field is missing
     */
    protected function validateTemplateAnswers(Job $job, array $answers): void
    {
        if (empty($answers)) {
            return;
        }
        $template = $job->jobTemplate;
        if (! $template || ! $template->relationLoaded('fields')) {
            throw new HttpException(422, __('job::messages.job_applications.template_fields_not_available'));
        }
        $fieldsById = $template->fields->keyBy('id');
        $answeredIds = [];
        foreach ($answers as $item) {
            $fieldId = (int) ($item['field_id'] ?? 0);
            $value = $item['value'] ?? null;
            if (! $fieldsById->has($fieldId)) {
                throw new HttpException(422, __('job::messages.job_applications.invalid_field_id', ['id' => $fieldId]));
            }
            $field = $fieldsById->get($fieldId);
            if ($field->is_required && ($value === null || $value === '' || (is_array($value) && empty($value)))) {
                throw new HttpException(422, __('job::messages.job_applications.required_field_missing', ['label' => $field->label]));
            }
            $answeredIds[] = $fieldId;
        }
    }

    /**
     * Persist template field answers for an application.
     *
     * @param  array<int, array{field_id: int, value: mixed}>  $answers
     */
    protected function saveFieldAnswers(int $applicationId, array $answers): void
    {
        foreach ($answers as $item) {
            $fieldId = (int) ($item['field_id'] ?? 0);
            $value = $item['value'] ?? null;
            $valueStr = is_array($value) ? json_encode($value) : (string) $value;
            JobApplicationFieldAnswer::create([
                'job_application_id' => $applicationId,
                'job_template_field_id' => $fieldId,
                'value' => $valueStr !== '' ? $valueStr : null,
            ]);
        }
    }

    /**
     * Get candidates (applications) for a job. Employer only (job must belong to employer).
     *
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     *
     * @throws HttpException When job not found or not owned by employer
     */
    public function getCandidatesForJob(int $jobId, int $employerId, array $filters = []): array
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new HttpException(404, __('job::messages.jobs.not_found'));
        }
        if ((int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }
        return $this->jobApplicationRepository->getByJobId($jobId, $filters);
    }

    /**
     * Get one application (candidate) detail for employer. Application's job must belong to employer.
     */
    public function getApplicationForEmployer(int $applicationId, int $employerId): ?JobApplication
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job', 'candidate', 'fieldAnswers.jobTemplateField', 'notes.user']);
        if (! $application) {
            return null;
        }
        if ((int) $application->job->employer_id !== $employerId) {
            return null;
        }
        return $application;
    }

    /**
     * Get full candidate details for employer: application, candidate info, field answers, notes, tags, attachments.
     *
     * @throws HttpException 404 when application does not exist or has no job
     * @throws HttpException 403 when application exists but the job does not belong to this employer
     */
    public function getCandidateDetailsForEmployer(int $applicationId, int $employerId): JobApplication
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, [
            'job',
            'candidate',
            'fieldAnswers.jobTemplateField',
            'notes.user',
            'tags',
            'internalAttachments',
        ]);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.application_not_found'));
        }
        if (! $application->relationLoaded('job') || ! $application->job) {
            throw new HttpException(404, __('job::messages.job_applications.application_or_job_not_found'));
        }
        if ((int) $application->job->employer_id !== (int) $employerId) {
            throw new HttpException(403, __('job::messages.job_applications.application_not_your_job'));
        }
        return $application;
    }

    /**
     * List applications submitted by the candidate (my applications). For consumer use.
     *
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getMyApplications(int $candidateId, array $filters = []): array
    {
        return $this->jobApplicationRepository->getByCandidateId($candidateId, $filters);
    }

    /**
     * Get one application for the candidate (owner of the application).
     */
    public function getApplicationForCandidate(int $applicationId, int $candidateId): ?JobApplication
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, [
            'job.category',
            'job.employer:id,name',
            'fieldAnswers.jobTemplateField',
            'notes.user',
            'candidate'
        ]);
        if (! $application) {
            return null;
        }
        if ((int) $application->candidate_id !== $candidateId) {
            return null;
        }
        return $application;
    }

    public function getKanbanForEmployer(int $jobId, int $employerId): array
    {
        $job = $this->jobRepository->find($jobId);

        if (! $job || (int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.unauthorized'));
        }

        $grouped = $this->jobApplicationRepository->getKanbanByJobId($jobId);

        $result = [];

        foreach (ApplicationStatus::cases() as $status) {
            $result[$status->value] = $grouped->get($status->value, collect());
        }

        return $result;
    }


    /**
     * Get notes for an application (candidate only). Candidate sees all notes on their application.
     *
     * @return \Illuminate\Support\Collection<int, \Modules\Job\Models\JobApplicationNote>
     *
     * @throws HttpException When application not found or user is not the candidate
     */
    public function getNotesForCandidate(int $applicationId, int $candidateId): \Illuminate\Support\Collection
    {
        $application = $this->getApplicationForCandidate($applicationId, $candidateId);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        return $this->noteRepository->getByApplicationId($applicationId);
    }

    /**
     * Add a note to an application (candidate only). E.g. follow-up message from the candidate.
     *
     * @throws HttpException When application not found or user is not the candidate
     */
    /**
     * Add a note to an application. Author is the authenticated user (user_id).
     * Allowed if user is the candidate or the job employer.
     */
    public function addNote(int $applicationId, int $authorUserId, string $content): \Modules\Job\Models\JobApplicationNote
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job']);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        $isCandidate = (int) $application->candidate_id === $authorUserId;
        $isEmployer = (int) $application->job->employer_id === $authorUserId;
        if (! $isCandidate && ! $isEmployer) {
            throw new HttpException(403, __('job::messages.job_applications.not_found'));
        }
        $note = $this->noteRepository->create([
            'job_application_id' => $applicationId,
            'user_id' => $authorUserId,
            'content' => $content,
        ]);
        return $note->load('user:id,name');
    }

    /**
     * @deprecated Use addNote() with author from auth. Kept for backwards compatibility.
     */
    public function addNoteForCandidate(int $applicationId, int $candidateId, string $content): \Modules\Job\Models\JobApplicationNote
    {
        return $this->addNote($applicationId, $candidateId, $content);
    }

    /**
     * Move a single application to a new stage. Employer only. Logs activity and returns updated stats.
     *
     * @return array{application: JobApplication, statistics: array<string, int>}
     *
     * @throws HttpException When job not found, not owned by employer, or application not found
     */
    public function moveStage(int $applicationId, ApplicationStatus $toStatus, int $employerId): array
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job']);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        if ((int) $application->job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }

        $fromStatus = $application->status;
        if ($fromStatus === $toStatus) {
            $stats = $this->jobApplicationRepository->getCountsByStatusForJob($application->job_id);
            return ['application' => $application->fresh(['job', 'candidate']), 'statistics' => $stats];
        }

        return DB::transaction(function () use ($application, $fromStatus, $toStatus, $employerId) {
            $this->jobApplicationRepository->update($application->id, ['status' => $toStatus]);
            $updated = $this->jobApplicationRepository->findWithRelations($application->id, ['job', 'candidate']);
            $statistics = $this->jobApplicationRepository->getCountsByStatusForJob($application->job_id);
            return ['application' => $updated, 'statistics' => $statistics];
        });
    }

    /**
     * Set employer rating (1-5) on an application. Employer only.
     *
     * @throws HttpException When application not found or job not owned by employer
     */
    public function setRating(int $applicationId, int $rating, int $employerId, ?string $description): JobApplication
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job', 'candidate']);
        if (! $application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        if ((int) $application->job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }

        $this->jobApplicationRepository->update($application->id, ['rating' => $rating,'rating_note' => $description]);
        return $this->jobApplicationRepository->findWithRelations($application->id, ['job', 'candidate']);
    }

    /**
     * Bulk move multiple applications to a new stage. Employer only. All applications must belong to the same job owned by the employer.
     *
     * @param  array<int>  $applicationIds
     * @return array{applications: \Illuminate\Support\Collection, statistics: array<string, int>}
     *
     * @throws HttpException When job not found, not owned, or any application not found / not in job
     */
    public function moveStageBulk(array $applicationIds, ApplicationStatus $toStatus, int $employerId): array
    {
        $applicationIds = array_values(array_unique(array_filter($applicationIds)));
        if ($applicationIds === []) {
            throw new HttpException(422, __('job::messages.job_applications.no_applications_selected'));
        }

        $applications = $this->jobApplicationRepository->getByIds($applicationIds);
        if ($applications->isEmpty()) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        $jobId = $applications->first()->job_id;
        if ($applications->pluck('job_id')->unique()->count() > 1) {
            throw new HttpException(422, __('job::messages.job_applications.bulk_must_same_job'));
        }
        $job = $this->jobRepository->find($jobId);
        if (! $job || (int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }
        if ($applications->count() !== count($applicationIds)) {
            throw new HttpException(422, __('job::messages.job_applications.some_applications_not_found'));
        }

        return DB::transaction(function () use ($applications, $toStatus, $employerId, $jobId) {
            foreach ($applications as $application) {
                $fromStatus = $application->status;
                if ($fromStatus === $toStatus) {
                    continue;
                }
                $this->jobApplicationRepository->update($application->id, ['status' => $toStatus]);
            }
            $updatedIds = $applications->pluck('id')->all();
            $updated = $this->jobApplicationRepository->getByIdsForJob($jobId, $updatedIds);
            $statistics = $this->jobApplicationRepository->getCountsByStatusForJob($jobId);
            return ['applications' => $updated, 'statistics' => $statistics];
        });
    }

    /**
     * Get Kanban column statistics (count per stage) for a job. Employer only.
     *
     * @return array<string, int>
     *
     * @throws HttpException When job not found or not owned
     */
    public function getStatisticsForJob(int $jobId, int $employerId): array
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new HttpException(404, __('job::messages.jobs.not_found'));
        }
        if ((int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }
        return $this->jobApplicationRepository->getCountsByStatusForJob($jobId);
    }

    /**
     * Get candidates for Kanban board grouped by status with independent pagination per column.
     *
     * @param  array{per_page?: int, sort?: string, search?: string, page_new?: int, page_under_review?: int, page_shortlisted?: int, page_interviews?: int, page_job_offer?: int, page_hired?: int, page_rejected?: int}  $params
     * @return array{columns: array<string, array{candidates: \Illuminate\Support\Collection, meta: array{current_page: int, last_page: int, per_page: int, total: int}}>}
     *
     * @throws HttpException When job not found or not owned by employer
     */
    public function getCandidatesKanban(int $jobId, int $employerId, array $params = []): array
    {
        $job = $this->jobRepository->find($jobId);
        if (! $job) {
            throw new HttpException(404, __('job::messages.jobs.not_found'));
        }
        if ((int) $job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }

        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 10)));
        $sort = $params['sort'] ?? 'latest';
        $search = isset($params['search']) ? trim((string) $params['search']) : null;
        $statuses = ApplicationStatus::values();
        $columns = [];

        foreach ($statuses as $status) {
            $pageKey = 'page_' . $status;
            $page = max(1, (int) ($params[$pageKey] ?? 1));
            $result = $this->jobApplicationRepository->getPaginatedByJobAndStatus($jobId, $status, $page, $perPage, $sort, $search);
            $columns[$status] = [
                'candidates' => $result['data'],
                'meta' => $result['meta'],
            ];
        }

        return ['columns' => $columns];
    }

    /**
     * Duplicate prevention: one application per candidate per job.
     */
    public function ensureNotDuplicate(int $candidateId, int $jobId): void
    {
        if ($this->jobApplicationRepository->existsByCandidateAndJob($candidateId, $jobId)) {
            throw new HttpException(422, __('job::messages.job_applications.duplicate_application'));
        }
    }

    protected function sendSuccessNotification(User $candidate, JobApplication $application, Job $job): void
    {
        $jobTitle = $job->title ?? '';
        $title = ['en' => 'Application submitted successfully', 'ar' => 'تم تقديم الطلب بنجاح'];
        $body = [
            'en' => "Your application for \"{$jobTitle}\" has been received and validated.",
            'ar' => "تم استلام طلبك لوظيفة \"{$jobTitle}\" والتحقق منه.",
        ];

        $this->emailService->sendJobApplicationSuccess($candidate, $application);
    }

    protected function sendRejectionNotification(User $candidate, JobApplication $application, string $reason, Job $job): void
    {
        $jobTitle = $job->title ?? '';
        $title = ['en' => 'Application could not be accepted', 'ar' => 'تعذر قبول الطلب'];
        $body = [
            'en' => "Your application for \"{$jobTitle}\" could not be accepted. Reason: {$reason}",
            'ar' => "تعذر قبول طلبك لوظيفة \"{$jobTitle}\". السبب: {$reason}",
        ];

        $this->emailService->sendJobApplicationRejected($candidate, $application, $reason);
    }

    public function deleteApplication(int $applicationId, int $employerId): bool
    {
        $application = $this->jobApplicationRepository->findWithRelations($applicationId, ['job']);
        if (!$application) {
            throw new HttpException(404, __('job::messages.job_applications.not_found'));
        }
        if ((int) $application->job->employer_id !== $employerId) {
            throw new HttpException(403, __('job::messages.jobs.unauthorized'));
        }

        return $this->jobApplicationRepository->delete($applicationId);
    }
}
