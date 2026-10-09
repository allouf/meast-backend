<?php

namespace Modules\Job\Services;

use Modules\Job\Enums\JobStatus;
use Modules\Job\Library\JobElasticsearch as JobModuleElasticsearch;
use Modules\Job\Models\Job;
use Modules\Job\Repositories\Interfaces\JobRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class JobService
{
    public function __construct(
        protected JobRepositoryInterface $jobRepository
    ) {}

    /**
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getAllForAdmin(array $filters = []): array
    {
        if ($this->shouldUseElasticsearch($filters)) {
            return $this->searchWithElasticsearch($filters);
        }
        return $this->jobRepository->all($filters);
    }

    public function getById(int $id): ?Job
    {
        return $this->jobRepository->find($id);
    }

    /**
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
    public function getMyJobs(int $userId, array $filters = []): array
    {
        return $this->jobRepository->getByUserId($userId, $filters);
    }

    /**
     * Public listing (active jobs only by default).
     *
     * @return array{data: \Illuminate\Support\Collection, meta: array}
     */
   public function getActiveJobs(array $filters = []): array
    {
        $filters['status'] = $filters['status'] ?? JobStatus::ACTIVE->value;
        if ($this->shouldUseElasticsearch($filters)) {
            return $this->searchWithElasticsearch($filters);
        }
        return $this->jobRepository->all($filters);
    }

    /**
     * Use Elasticsearch when search or any filter is present (like products/stores).
     */
    private function shouldUseElasticsearch(array $filters): bool
    {
        $hasSearch = ! empty($filters['search']);
        $hasFilters = ! empty($filters['employer_id']) || ! empty($filters['category_id'])
            || ! empty($filters['country_code']) || ! empty($filters['employment_type'])
            || ! empty($filters['experience_level']) || ! empty($filters['work_mode'])
            || ! empty($filters['application_deadline_from'])
            || ! empty($filters['application_deadline_to'])
            || isset($filters['min_salary']) || isset($filters['max_salary'])
            || ! empty($filters['company_name']);
        return $hasSearch || $hasFilters;
    }

    /**
     * Search jobs via Elasticsearch and return paginated result with full Job models.
     *
     * @return array{data: \Illuminate\Support\Collection, meta: array}
      */
    private function searchWithElasticsearch(array $filters): array
    {
        $es = new JobModuleElasticsearch();
        $result = $es->searchJobsWithFilters($filters);
        $jobIds = collect($result['hits']['hits'] ?? [])
            ->pluck('_id')
            ->map(fn($id) => (int) $id)
            ->toArray();
        $total = $result['hits']['total']['value'] ?? 0;
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);

        if (empty($jobIds)) {
            return [
                'data' => collect([]),
                'meta' => [
                    'total' => 0,
                    'current_page' => $page,
                    'last_page' => 0,
                    'per_page' => $perPage,
                    'has_more' => false,
                ]
            ];
        }

        $jobs = Job::whereIn('id', $jobIds)
            ->with(['employer.company', 'category', 'jobTemplate'])
            ->get()
            ->sortBy(function ($job) use ($jobIds) {
                return array_search($job->id, $jobIds);
            })
            ->values();

        $paginator = new LengthAwarePaginator(
            $jobs,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'data' => $paginator->items(),
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'has_more' => $paginator->hasMorePages(),
            ]
        ];
    }

    public function createJob(int $userId, array $data): Job
    {
        $data['employer_id'] = $userId;
        $data['status'] = $data['status'] ?? JobStatus::DRAFT->value;
        return $this->jobRepository->create($data);
    }

    public function updateJob(int $id, array $data, ?int $userId = null): ?Job
    {
        $job = $this->jobRepository->find($id);
        if (! $job) {
            return null;
        }
        if ($userId !== null && $job->employer_id != $userId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update'));
        }
        unset($data['employer_id']);
        return $this->jobRepository->update($id, $data);
    }

    public function deleteJob(int $id, ?int $userId = null): bool
    {
        $job = $this->jobRepository->find($id);
        if (! $job) {
            return false;
        }
        if ($userId !== null && $job->employer_id != $userId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_delete'));
        }
        return $this->jobRepository->delete($id);
    }

    public function updateStatus(int $id, string $status, ?int $userId = null): ?Job
    {
        if (! in_array($status, array_column(JobStatus::cases(), 'value'), true)) {
            throw new HttpException(400, __('job::messages.jobs.invalid_status'));
        }
        $job = $this->jobRepository->find($id);
        if (! $job) {
            return null;
        }
        if ($userId !== null && $job->employer_id != $userId) {
            throw new HttpException(403, __('job::messages.jobs.cannot_update_status'));
        }
        return $this->jobRepository->updateStatus($id, $status);
    }

    public function getStatistics(?int $employerId = null): array
    {
        return $this->jobRepository->statistics($employerId);
    }
}
