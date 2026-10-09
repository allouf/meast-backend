<?php

namespace Modules\Services\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Modules\Services\Models\BoostPlan;
use Modules\Services\Repositories\Interfaces\BoostPlanRepositoryInterface;

class BoostPlanService
{
    public function __construct(
        protected BoostPlanRepositoryInterface $boostPlanRepository
    ) {}

    public function getPlans()
    {
        return $this->boostPlanRepository->all();
    }

    public function getPaginated(array $filters = []): LengthAwarePaginator
    {
        return $this->boostPlanRepository->getPaginated($filters);
    }

    public function findById($id): ?BoostPlan
    {
        return $this->boostPlanRepository->findById($id);
    }

    public function createPlan(array $data): BoostPlan
    {
        $variants = $data['variants'] ?? [];
        unset($data['variants']);

        return DB::transaction(function () use ($data, $variants) {
            $plan = $this->boostPlanRepository->create($data);
            $this->syncVariants($plan, $variants);

            return $plan->fresh(['variants']);
        });
    }

    public function updatePlan(array $data, $id): BoostPlan
    {
        $variants = null;
        if (array_key_exists('variants', $data)) {
            $variants = $data['variants'];
            unset($data['variants']);
        }

        $boostPlan = $this->boostPlanRepository->findById($id);
        throw_if(! $boostPlan, ModelNotFoundException::class);

        return DB::transaction(function () use ($data, $boostPlan, $variants) {
            $this->boostPlanRepository->update($data, $boostPlan);
            if ($variants !== null) {
                $this->syncVariants($boostPlan, $variants);
            }

            return $boostPlan->fresh(['variants']);
        });
    }

    public function toggleActivation(int $id): BoostPlan
    {
        $plan = $this->boostPlanRepository->findById($id);
        throw_if(! $plan, ModelNotFoundException::class);

        return $this->boostPlanRepository->toggleActive($plan, ! $plan->is_active);
    }

    public function delete(int $id): void
    {
        $boostPlan = $this->boostPlanRepository->findById($id);
        throw_if(! $boostPlan, ModelNotFoundException::class);
        $this->boostPlanRepository->delete($boostPlan);
    }

    protected function syncVariants(BoostPlan $plan, array $variants): void
    {
        $plan->variants()->delete();
        foreach ($variants as $variant) {
            $plan->variants()->create([
                'country_code' => $variant['country_code'] ?? null,
                'price' => $variant['price'],
                'currency' => $variant['currency'],
                'is_active' => $variant['is_active'] ?? true,
            ]);
        }
    }
}
