<?php

namespace Modules\Job\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Job\Enums\ApplicationStatus;
use Modules\Job\Filters\JobApplicationFilter;
use Modules\Job\Models\JobApplication;
use Modules\Job\Repositories\Interfaces\JobApplicationRepositoryInterface;

class JobApplicationRepository implements JobApplicationRepositoryInterface
{
    use PaginateTrait;

    public function __construct(protected JobApplication $model) {}

    public function create(array $data): JobApplication
    {
        return $this->model->create($data);
    }

    public function existsByCandidateAndJob(int $candidateId, int $jobId): bool
    {
        return $this->model->where('candidate_id', $candidateId)->where('job_id', $jobId)->exists();
    }

    public function findWithRelations(int $id, array $relations = []): ?JobApplication
    {
        $query = $this->model->newQuery();
        if ($relations !== []) {
            $query->with($relations);
        }
        return $query->find($id);
    }

    public function update(int $id, array $data): ?JobApplication
    {
        $application = $this->model->find($id);
        if (! $application) {
            return null;
        }
        $application->update($data);
        return $application->fresh(['job', 'candidate']);
    }

    public function getByJobId(int $jobId, array $filters = []): array
    {
        $query = $this->model->with(['candidate', 'job'])->where('job_id', $jobId)->orderByDesc('created_at');
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getByCandidateId(int $candidateId, array $filters = []): array
    {
        $query = $this->model->with(['job.category', 'job.employer:id,name,photo', 'notes.user', 'fieldAnswers.jobTemplateField'])->where('candidate_id', $candidateId);
        $query = (new JobApplicationFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getByIds(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }
        return $this->model->with(['job', 'candidate', 'notes.user'])->whereIn('id', $ids)->get();
    }

    public function getByIdsForJob(int $jobId, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }
        return $this->model->with(['job', 'candidate', 'notes.user'])->where('job_id', $jobId)->whereIn('id', $ids)->get();
    }

    public function getCountsByStatusForJob(int $jobId): array
    {
        $counts = $this->model->where('job_id', $jobId)->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status')->all();
        $result = array_fill_keys(array_column(ApplicationStatus::cases(), 'value'), 0);
        return array_merge($result, $counts);
    }

    public function getPaginatedByJobAndStatus(int $jobId, string $status, int $page, int $perPage, string $sort = 'latest', ?string $search = null): array
    {
        $query = $this->model->with(['job', 'candidate', 'tags', 'notes.user'])
            ->where('job_id', $jobId)
            ->where('status', $status);

        $filters = ['sort' => $sort];
        $query = (new JobApplicationFilter($filters))->apply($query);

        $search = $search !== null ? trim($search) : null;
        if ($search !== null && $search !== '') {
            $keywords = preg_split('/\s+/', $search) ?: [];
            $query->whereHas('candidate', function (Builder $q) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $keyword = trim((string) $keyword);
                    if ($keyword === '') {
                        continue;
                    }
                    $term = '%' . $keyword . '%';
                    $q->where(function (Builder $inner) use ($term) {
                        $inner->where('name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    });
                }
            });
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        return [
            'data' => collect($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    public function getKanbanByJobId(int $jobId): Collection
    {
        return $this->model->with(['candidate:id,name,photo', 'tags:id,name,color', 'notes.user'])->where('job_id', $jobId)->orderByDesc('created_at')->get()->groupBy(function($app) {
            return $app->status->value;
        });
    }

    public function delete(int $id): bool
    {
        $application = $this->model->find($id);
        if (!$application) {
            return false;
        }
        return (bool) $application->delete();
    }
}
