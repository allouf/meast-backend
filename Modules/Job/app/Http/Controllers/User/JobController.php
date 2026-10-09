<?php

namespace Modules\Job\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Job\Services\ProfileCompletionService;
use Exception;
use Illuminate\Http\Request;
use Modules\Job\Http\Requests\User\Job\StoreJobRequest;
use Modules\Job\Http\Requests\User\Job\UpdateJobRequest;
use Modules\Job\Http\Requests\User\Job\UpdateJobStatusRequest;
use Modules\Job\Http\Resources\User\Job\JobApplicationCandidateResource;
use Modules\Job\Http\Resources\User\Job\JobResource;
use Modules\Job\Http\Resources\User\Company\CompanyProfileResource;
use Modules\Job\Services\CompanyProfileService;
use Modules\Job\Services\JobApplicationService;
use Modules\Job\Services\JobService;
use Illuminate\Support\Facades\Auth;
use Modules\Job\Http\Resources\User\Job\JobStatisticsResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JobController extends Controller
{
    public function __construct(
        protected JobService $jobService,
        protected JobApplicationService $jobApplicationService,
        protected ProfileCompletionService $profileCompletionService,
        protected CompanyProfileService $companyProfileService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['per_page', 'page', 'scope', 'category_id', 'status', 'country_code', 'employment_type', 'work_mode', 'experience_level', 'search', 'sort', 'application_deadline_from', 'application_deadline_to', 'min_salary', 'max_salary', 'company_name']);
            $scope = isset($filters['scope']) ? strtolower((string) $filters['scope']) : 'jobs';

            if ($scope === 'companies') {
                $result = $this->companyProfileService->getPaginatedCompanies($filters);
                $data = ['companies' => CompanyProfileResource::collection($result['data']), 'meta' => $result['meta']];
                $this->mergeJobProfileCompletion($data, $request);
                return $this->successResponse(__('job::messages.companies.retrieved_successfully'), $data);
            }

            $result = $this->jobService->getActiveJobs($filters);
            $data = ['jobs' => JobResource::collection($result['data']), 'meta' => $result['meta']];
            $this->mergeJobProfileCompletion($data, $request);
            return $this->successResponse(__('job::messages.jobs.retrieved_successfully'), $data);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function show(Request $request, int $id)
    {
        try {
            $job = $this->jobService->getById($id);
            if (! $job) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            $job->unsetRelation('jobTemplate');
            $job->load('jobTemplate.fields');
            $data = ['job' => new JobResource($job)];
            $this->mergeJobProfileCompletion($data, $request);
            return $this->successResponse(__('job::messages.jobs.retrieved_successfully'), $data);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function myJobs(Request $request)
    {
        try {
            $userId = Auth::id();
            $filters = $request->only(['per_page', 'page', 'status', 'category_id', 'work_mode', 'country_code', 'employment_type', 'search', 'sort', 'application_deadline_from', 'application_deadline_to', 'min_salary', 'max_salary']);
            $result = $this->jobService->getMyJobs($userId, $filters);
            return $this->successResponse(__('job::messages.jobs.retrieved_successfully'), ['jobs' => JobResource::collection($result['data']), 'meta' => $result['meta']]);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function store(StoreJobRequest $request)
    {
        try {
            $userId = Auth::id();
            $job = $this->jobService->createJob($userId, $request->validated());
            $data = ['job' => new JobResource($job)];
            $this->mergeJobProfileCompletion($data, $request);
            return $this->successResponse(__('job::messages.jobs.created_successfully'), $data, 201);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function update(UpdateJobRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $job = $this->jobService->updateJob($id, $request->validated(), $userId);
            if (! $job) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.jobs.updated_successfully'), new JobResource($job));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function destroy(int $id)
    {
        try {
            $userId = Auth::id();
            $deleted = $this->jobService->deleteJob($id, $userId);
            if (! $deleted) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.jobs.deleted_successfully'), null);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function updateStatus(UpdateJobStatusRequest $request, int $id)
    {
        try {
            $userId = Auth::id();
            $job = $this->jobService->updateStatus($id, $request->input('status'), $userId);
            if (! $job) {
                return $this->errorResponse(__('job::messages.jobs.not_found'), 404);
            }
            return $this->successResponse(__('job::messages.jobs.status_updated_successfully'), new JobResource($job));
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getCandidates(Request $request, int $jobId)
    {
        try {
            $employerId = Auth::id();
            $filters = $request->only(['per_page']);
            $result = $this->jobApplicationService->getCandidatesForJob($jobId, $employerId, $filters);
            return $this->successResponse(__('job::messages.job_applications.candidates_retrieved_successfully'), ['candidates' => JobApplicationCandidateResource::collection($result['data']), 'meta' => $result['meta']]);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getCandidateStatistics(int $jobId)
    {
        try {
            $employerId = Auth::id();
            $statistics = $this->jobApplicationService->getStatisticsForJob($jobId, $employerId);
            return $this->successResponse(__('job::messages.job_applications.statistics_retrieved_successfully'), ['statistics' => $statistics]);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    public function getCandidatesKanban(Request $request, int $jobId)
    {
        try {
            $employerId = Auth::id();
            $params = $request->only(['per_page', 'sort', 'search', 'page_new', 'page_under_review', 'page_shortlisted', 'page_interviews', 'page_job_offer', 'page_hired', 'page_rejected']);
            $result = $this->jobApplicationService->getCandidatesKanban($jobId, $employerId, $params);
            $data = ['columns' => collect($result['columns'])->map(fn($column) => ['candidates' => JobApplicationCandidateResource::collection($column['candidates']), 'meta' => $column['meta']])->all()];
            return $this->successResponse(__('job::messages.job_applications.candidates_retrieved_successfully'), $data);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    protected function mergeJobProfileCompletion(array &$data, Request $request): void
    {
        $percentage = $request->attributes->get('job_profile_completion_percentage');
        if ($percentage === null && Auth::guard('api')->check()) {
            $percentage = $this->profileCompletionService->getPercentage(Auth::guard('api')->user());
        }
        $data['profile_completion_percentage'] = $percentage;
    }

    public function statistics(Request $request)
    {
        try {
            $employerId = $request->user()?->id;

            $stats = $this->jobService->getStatistics($employerId);

            return $this->successResponse(
                __('job::messages.jobs.statistics_retrieved_successfully'),
                new JobStatisticsResource($stats)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }
    }
}
