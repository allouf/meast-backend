<?php

namespace Modules\Job\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Job\Filters\SkillFilter;
use Modules\Job\Models\Skill;
use Modules\Job\Repositories\Interfaces\SkillRepositoryInterface;

class SkillRepository implements SkillRepositoryInterface
{
    use PaginateTrait;

    protected Skill $model;

    public function __construct(Skill $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = []): array
    {
        $query = $this->model->newQuery();
        $query = (new SkillFilter($filters))->apply($query);

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function find(int $id): ?Skill
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?Skill
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Skill
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ?Skill
    {
        $skill = $this->model->find($id);
        if ($skill) {
            $skill->update($data);
            return $skill->fresh();
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $skill = $this->model->find($id);
        return $skill ? $skill->delete() : false;
    }

    public function toggleStatus(int $id): ?Skill
    {
        $skill = $this->model->find($id);
        if (! $skill) {
            return null;
        }
        $skill->update(['is_active' => ! $skill->is_active]);
        return $skill->fresh();
    }
}
