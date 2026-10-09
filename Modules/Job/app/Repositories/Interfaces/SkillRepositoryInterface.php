<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\Skill;

interface SkillRepositoryInterface
{
    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function all(array $filters = []): array;

    public function find(int $id): ?Skill;

    public function findBySlug(string $slug): ?Skill;

    public function create(array $data): Skill;

    public function update(int $id, array $data): ?Skill;

    public function delete(int $id): bool;

    public function toggleStatus(int $id): ?Skill;
}
