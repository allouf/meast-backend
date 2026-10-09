<?php

namespace Modules\Services\Repositories\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\Milestone;

interface MilestoneRepositoryInterface
{
    /** @return LengthAwarePaginator<Milestone> */
    public function getByServiceProviderId(int $serviceProviderId, array $filters = []): LengthAwarePaginator;

    /** @return LengthAwarePaginator<Milestone> */
    public function getPublicActiveTemplates(array $filters = []): LengthAwarePaginator;

    /** @param  array<string>  $relations */
    public function findById(int $id, ?int $serviceProviderId = null, array $relations = []): ?Milestone;

    public function findTemplateById(int $id, ?int $serviceProviderId = null): ?Milestone;

    public function findPublicTemplateById(int $id): ?Milestone;

    public function findPublicActiveTemplateByServiceOfferId(int $serviceOfferId): ?Milestone;

    public function create(array $data): Milestone;

    public function update(Milestone $milestone, array $data): Milestone;

    public function delete(Milestone $milestone): void;

    public function toggleActivation(Milestone $milestone): Milestone;
}
