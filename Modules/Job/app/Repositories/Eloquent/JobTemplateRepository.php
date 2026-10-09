<?php

namespace Modules\Job\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Job\Filters\JobTemplateFilter;
use Modules\Job\Models\JobTemplate;
use Modules\Job\Repositories\Interfaces\JobTemplateRepositoryInterface;

class JobTemplateRepository implements JobTemplateRepositoryInterface
{
    use PaginateTrait;

    protected JobTemplate $model;

    public function __construct(JobTemplate $model)
    {
        $this->model = $model;
    }

    public function getByUserId(int $userId, array $filters = []): array|Collection
    {
        $query = $this->model->where('employer_id', $userId)->with(['category', 'fields'])->orderBy('updated_at', 'desc');
        $query = (new JobTemplateFilter($filters))->apply($query);
        
        $shouldPaginate = $filters['paginate'] ?? true;

        if (! $shouldPaginate) {
            return $query->get(); // Collection
        }
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find(int $id): ?JobTemplate
    {
        return $this->model->with(['category', 'fields'])->find($id);
    }

    public function findForUser(int $id, int $userId): ?JobTemplate
    {
        return $this->model->with(['category', 'fields'])
            ->where('id', $id)
            ->where(function ($q) use ($userId) {
                $q->where('employer_id', $userId)->orWhere('is_system_template', true);
            })
            ->first();
    }

    public function create(array $data): JobTemplate
    {
        $fields = $data['fields'] ?? [];
        unset($data['fields']);
        $template = $this->model->create($data);
        $this->syncFields($template, $fields);
        return $template->load(['category', 'fields']);
    }

    public function update(int $id, array $data): ?JobTemplate
    {
        $template = $this->model->find($id);
        if (! $template) {
            return null;
        }
        $fields = $data['fields'] ?? null;
        unset($data['fields']);
        $template->update($data);
        if ($fields !== null) {
            $this->syncFields($template, $fields);
        }
        return $template->fresh(['category', 'fields']);
    }

    public function delete(int $id): bool
    {
        $template = $this->model->find($id);
        return $template ? $template->delete() : false;
    }

    public function getSystemTemplatesByCategory(int $categoryId, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        return $this->model->with(['category', 'fields'])
            ->where('category_id', $categoryId)
            ->where('is_system_template', true)
            ->active()
            ->orderBy('title')
            ->paginate($perPage);
    }

    public function getSystemTemplates(array $filters = []): array
    {
        $query = $this->model->with(['category', 'fields'])
            ->where('is_system_template', true)
            ->active()
            ->orderBy('title');
        $query = (new JobTemplateFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getAvailableByCategory(int $categoryId, int $userId, array $filters = []): array
    {
        $query = $this->model->with(['category', 'fields'])
            ->where('category_id', $categoryId)
            ->where(function ($q) use ($userId) {
                $q->where('employer_id', $userId)->orWhere('is_system_template', true);
            })
            ->active()
            ->orderByRaw('is_system_template ASC, title ASC');

        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function all(array $filters = []): array
    {
        $query = $this->model->with(['category', 'employer', 'fields'])->withCount('jobs')->orderByDesc('updated_at');
        $query = (new JobTemplateFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function toggleStatus(int $id): ?JobTemplate
    {
        $template = $this->model->find($id);
        if (! $template) {
            return null;
        }
        $template->update(['is_active' => ! $template->is_active]);
        return $template->fresh(['category', 'fields']);
    }

    protected function syncFields(JobTemplate $template, array $fields): void
    {
        $template->fields()->delete();
        foreach ($fields as $index => $field) {
            $template->fields()->create([
                'label' => $field['label'],
                'type' => $field['type'],
                'validation_rules' => $field['validation_rules'] ?? null,
                'options' => $field['options'] ?? null,
                'sort_order' => $field['sort_order'] ?? $index,
                'is_required' => (bool) ($field['is_required'] ?? false),
            ]);
        }
    }
}
