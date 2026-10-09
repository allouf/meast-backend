<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Job\Enums\ApplicationStatus;
use Modules\Job\Http\Requests\User\Job\AttachTagRequest;
use Modules\Job\Http\Requests\User\Job\BulkMoveStageRequest;
use Modules\Job\Http\Requests\User\Job\MoveStageRequest;
use Modules\Job\Http\Requests\User\Job\SetApplicationRatingRequest;
use Modules\Job\Http\Requests\User\Job\StoreEmployerTagRequest;
use Modules\Job\Http\Requests\User\Job\StoreJobApplicationRequest;
use Modules\Job\Http\Requests\User\Job\StoreJobApplicationNoteRequest;
use Modules\Job\Http\Resources\User\Job\JobApplicationCandidateDetailResource;
use Modules\Job\Http\Resources\User\Job\JobApplicationCandidateResource;
use Modules\Job\Http\Resources\User\Job\JobApplicationInternalAttachmentResource;
use Modules\Job\Http\Resources\User\Job\JobApplicationNoteResource;
use Modules\Job\Http\Resources\User\Job\JobApplicationResource;
use Modules\Job\Http\Resources\User\Job\JobApplicationTagResource;
use Modules\Job\Services\JobApplicationInternalService;
use Modules\Job\Services\JobApplicationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\Job\Http\Requests\User\Job\UpdateEmployerTagRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobApplicationController extends Controller
{
    public function __construct(
        protected JobApplicationService $jobApplicationService,
        protected JobApplicationInternalService $internalService
    ) {}

    /**
     * List my job applications (as candidate).
     */
    public function myApplications(Request $request)
    {
        try {
            $candidateId = Auth::guard('api')->id();
            if (! $candidateId) {
                return $this->errorResponse(__('job::messages.unauthorized'), 401);
            }
            $filters = $request->only(['per_page', 'page', 'status', 'job_id', 'search', 'sort']);
            $result = $this->jobApplicationService->getMyApplications($candidateId, $filters);
            return $this->successResponse(
                __('job::messages.job_applications.retrieved_successfully'),
                [
                    'applications' => JobApplicationResource::collection($result['data']),
                    'meta' => $result['meta'],
                ]
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Get one of my job applications (as candidate).
     */
    public function showMyApplication(int $id)
    {
        try {
            $candidateId = Auth::guard('api')->id();
            if (! $candidateId) {
                return $this->errorResponse(__('job::messages.unauthorized'), 401);
            }
            $application = $this->jobApplicationService->getApplicationForCandidate($id, $candidateId);
            if (! $application) {
                return $this->errorResponse(__('job::messages.job_applications.application_not_found'), 404);
            }

            return $this->successResponse(
                __('job::messages.job_applications.retrieved_successfully'),
                new JobApplicationResource($application)
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Submit a job application (apply).
     */
    public function store(StoreJobApplicationRequest $request)
    {
        try {
            $candidateId = Auth::id();
            $validated = $request->validated();
            $application = $this->jobApplicationService->submit(
                $candidateId,
                (int) $validated['job_id'],
                $request->file('resume'),
                $validated['cover_letter'] ?? null,
                $validated['answers'] ?? []
            );
            return $this->successResponse(
                __('job::messages.job_applications.submitted_successfully'),
                new JobApplicationResource($application),
                201
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function showApplication(int $id)
    {
        try {
            $employerId = Auth::guard('api')->id();
            if (! $employerId) {
                return $this->errorResponse(__('job::messages.unauthorized'), 401);
            }
            $application = $this->jobApplicationService->getCandidateDetailsForEmployer($id, $employerId);
            return $this->successResponse(__('job::messages.job_applications.retrieved_successfully'), new JobApplicationCandidateDetailResource($application));
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function setRating(SetApplicationRatingRequest $request)
    {
        try {
            $employerId = Auth::id();
            $application = $this->jobApplicationService->setRating((int) $request->validated('application_id'), (int) $request->validated('rating'), $employerId,$request->validated('rating_note'));
            return $this->successResponse(__('job::messages.job_applications.rating_updated_successfully'), ['application' => new JobApplicationCandidateResource($application)]);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function moveStage(MoveStageRequest $request)
    {
        try {
            $employerId = Auth::id();
            $toStatus = ApplicationStatus::from($request->validated('stage'));
            $applicationId = (int) $request->validated('application_id');
            $result = $this->jobApplicationService->moveStage($applicationId, $toStatus, $employerId);
            return $this->successResponse(__('job::messages.job_applications.stage_updated_successfully'), ['application' => new JobApplicationCandidateResource($result['application']), 'statistics' => $result['statistics']]);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function moveStageBulk(BulkMoveStageRequest $request)
    {
        try {
            $employerId = Auth::id();
            $toStatus = ApplicationStatus::from($request->validated('stage'));
            $result = $this->jobApplicationService->moveStageBulk($request->validated('application_ids'), $toStatus, $employerId);
            return $this->successResponse(__('job::messages.job_applications.stage_updated_successfully'), ['applications' => JobApplicationCandidateResource::collection($result['applications']), 'statistics' => $result['statistics']]);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function addNote(StoreJobApplicationNoteRequest $request)
    {
        try {
            $userId = Auth::id();
            $validated = $request->validated();
            $note = $this->jobApplicationService->addNote((int) $validated['application_id'], $userId, $validated['content']);
            return $this->successResponse(__('job::messages.job_applications.note_added_successfully'), ['note' => new JobApplicationNoteResource($note)], 201);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getEmployerTags(Request $request)
    {
        try {
            $validated = $request->validate(['job_id' => 'required|exists:job_postings,id']);
            $employerId = Auth::id();
            $tags = $this->internalService->getEmployerTags((int) $validated['job_id'], $employerId);
            return $this->successResponse(__('job::messages.job_applications.tags_retrieved_successfully'), ['tags' => JobApplicationTagResource::collection($tags)]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function createEmployerTag(StoreEmployerTagRequest $request)
    {
        try {
            $employerId = Auth::id();
            $validated = $request->validated();
            $tag = $this->internalService->createTag((int) $validated['job_id'], $employerId, $validated['name'], $validated['color']);
            return $this->successResponse(__('job::messages.job_applications.tag_created_successfully'), ['tag' => new JobApplicationTagResource($tag)], 201);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateEmployerTag(UpdateEmployerTagRequest $request, int $id)
    {
        try {
            $employerId = Auth::id();
            $validated = $request->validated();

            $tag = $this->internalService->updateTag(
                $id,
                $employerId,
                $validated['name'],
                $validated['color']
            );

            return $this->successResponse(
                __('job::messages.job_applications.tag_updated_successfully'),
                ['tag' => new JobApplicationTagResource($tag)]
            );
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function attachTag(AttachTagRequest $request)
    {
        try {
            $validated = $request->validated();
            $applicationId = (int) $validated['application_id'];
            $tagId = (int) $validated['tag_id'];
            $employerId = Auth::id();
            $this->internalService->attachTag($applicationId, $tagId, $employerId);
            $tags = $this->internalService->getTagsForApplication($applicationId, $employerId);
            return $this->successResponse(__('job::messages.job_applications.tag_attached_successfully'), ['tags' => JobApplicationTagResource::collection($tags)]);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function detachTag(Request $request)
    {
        $request->validate(['application_id' => 'required|integer|exists:job_applications,id', 'tag_id' => 'required|integer|min:1']);
        try {
            $applicationId = (int) $request->input('application_id');
            $tagId = (int) $request->input('tag_id');
            $employerId = Auth::id();
            $this->internalService->detachTag($applicationId, $tagId, $employerId);
            return $this->successResponse(__('job::messages.job_applications.tag_detached_successfully'));
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate(['application_id' => 'required|integer|exists:job_applications,id', 'file' => 'required|file|mimes:pdf,doc,docx|max:10240']);
        try {
            $applicationId = (int) $request->input('application_id');
            $employerId = Auth::id();
            $attachment = $this->internalService->uploadAttachment($applicationId, $employerId, $request->file('file'));
            return $this->successResponse(__('job::messages.job_applications.attachment_uploaded_successfully'), ['attachment' => new JobApplicationInternalAttachmentResource($attachment)], 201);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function deleteAttachment(Request $request, int $attachmentId)
    {
        try {
            $employerId = Auth::id();
            $this->internalService->deleteAttachment($attachmentId, $employerId);
            return $this->successResponse(__('job::messages.job_applications.attachment_deleted_successfully'), null);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function downloadAttachment(Request $request, int $attachmentId): StreamedResponse
    {
        $employerId = Auth::id();
        $attachment = $this->internalService->getAttachmentForDownload($attachmentId, $employerId);
        if (! $attachment) {
            abort(404, __('job::messages.job_applications.attachment_not_found'));
        }
        $disk = Storage::disk(config('filesystems.default'));
        if (! $disk->exists($attachment->file_path)) {
            abort(404, __('job::messages.job_applications.attachment_not_found'));
        }
        return response()->streamDownload(function () use ($disk, $attachment) {
            $stream = $disk->readStream($attachment->file_path);
            fpassthru($stream);
            fclose($stream);
        }, $attachment->original_name, ['Content-Type' => $attachment->mime_type ?? 'application/octet-stream']);
    }
    public function deleteApplication(int $id)
    {
        try {
            $employerId = Auth::id();
            $this->jobApplicationService->deleteApplication($id, $employerId);
            return $this->successResponse(__('job::messages.job_applications.deleted_successfully'), null);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
