<?php

namespace Modules\Job\Repositories\Interfaces;

use Modules\Job\Models\JobApplication;

interface JobApplicationRepositoryInterface
{
    public function create(array $data): JobApplication;

    public function existsByCandidateAndJob(int $candidateId, int $jobId): bool;

    /** @param array<string> $relations */
    public function findWithRelations(int $id, array $relations = []): ?JobApplication;

    public function update(int $id, array $data): ?JobApplication;

    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function getByJobId(int $jobId, array $filters = []): array;

    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function getByCandidateId(int $candidateId, array $filters = []): array;

    /** @param array<int> $ids @return \Illuminate\Support\Collection<int, JobApplication> */
    public function getByIds(array $ids): \Illuminate\Support\Collection;

    /** @param array<int> $ids @return \Illuminate\Support\Collection<int, JobApplication> */
    public function getByIdsForJob(int $jobId, array $ids): \Illuminate\Support\Collection;

    /** @return array<string, int> */
    public function getCountsByStatusForJob(int $jobId): array;

    /** @return array{data: \Illuminate\Support\Collection, meta: array} */
    public function getPaginatedByJobAndStatus(int $jobId, string $status, int $page, int $perPage, string $sort = 'latest', ?string $search = null): array;

    public function getKanbanByJobId(int $jobId): \Illuminate\Support\Collection;

    public function delete(int $id): bool;
}
