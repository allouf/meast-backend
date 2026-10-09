<?php

namespace Modules\Job\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Job\Enums\EmploymentType;
use Modules\Job\Enums\ExperienceLevel;
use Modules\Job\Enums\JobStatus;
use Modules\Job\Enums\WorkMode;
use Modules\Job\Filters\JobFilter;
use Modules\Job\Models\Job;
use Modules\Job\Repositories\Interfaces\JobRepositoryInterface;

class JobRepository implements JobRepositoryInterface
{
    use PaginateTrait;

    protected Job $model;

    public function __construct(Job $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = []): array
    {
        $query = $this->model->with(['employer.company', 'category', 'jobTemplate.fields']);
        $query = (new JobFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find(int $id): ?Job
    {
        return $this->model->with([
            'employer.company',
            'category',
            'jobTemplate',
            'latestApplications' => function ($q) {
                $q->latest()
                    ->limit(3)
                    ->with([
                        'candidate:id,name,photo',
                        'job.category:id,name',
                        'job.employer:id,name,photo',
                        'tags:id,name,color'
                    ]);
            }
        ])->find($id);
    }

    public function getByUserId(int $userId, array $filters = []): array
    {
        $query = $this->model->where('employer_id', $userId)->with(['category', 'jobTemplate']);
        $query = (new JobFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function create(array $data): Job
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Job
    {
        $job = $this->model->find($id);
        if (! $job) return null;
        $job->update($data);
        return $job->fresh(['employer', 'category', 'jobTemplate']);
    }

    public function delete(int $id): bool
    {
        $job = $this->model->find($id);
        return $job ? $job->delete() : false;
    }

    public function updateStatus(int $id, string $status): ?Job
    {
        $job = $this->model->find($id);
        if (! $job) return null;
        $job->update(['status' => $status]);
        return $job->fresh(['employer', 'category', 'jobTemplate']);
    }

    public function statistics(?int $employerId = null): array
    {
        $query = $this->model->newQuery();

        if ($employerId) {
            $query->where('employer_id', $employerId);
        }

        $total = (clone $query)->count();

        $fillEnumCounts = function ($enumClass, $data) {
            return collect($enumClass::cases())->mapWithKeys(fn($case) => [
                $case->value => $data[$case->value] ?? 0
            ])->toArray();
        };

        $byStatus = $fillEnumCounts(JobStatus::class, (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray());

        $byEmploymentType = $fillEnumCounts(EmploymentType::class, (clone $query)
            ->selectRaw('employment_type, COUNT(*) as count')
            ->groupBy('employment_type')
            ->pluck('count', 'employment_type')
            ->toArray());

        $byWorkMode = $fillEnumCounts(WorkMode::class, (clone $query)
            ->selectRaw('work_mode, COUNT(*) as count')
            ->groupBy('work_mode')
            ->pluck('count', 'work_mode')
            ->toArray());

        $byExperienceLevel = $fillEnumCounts(ExperienceLevel::class, (clone $query)
            ->selectRaw('experience_level, COUNT(*) as count')
            ->groupBy('experience_level')
            ->pluck('count', 'experience_level')
            ->toArray());

        $monthlyJobs = (clone $query)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $topJobs = (clone $query)
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->limit(5)
            ->get(['id', 'title'])
            ->map(fn($job) => [
                'id' => $job->id,
                'title' => $job->title,
                'applications_count' => $job->applications_count
            ])->toArray();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_employment_type' => $byEmploymentType,
            'by_work_mode' => $byWorkMode,
            'by_experience_level' => $byExperienceLevel,
            'monthly_jobs' => $monthlyJobs,
            'top_jobs' => $topJobs,
        ];
    }
}
