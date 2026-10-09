<?php

namespace Modules\Services\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Models\Milestone;
use Modules\Services\Models\MilestoneService as MilestoneServiceLine;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Repositories\Interfaces\MilestoneRepositoryInterface;
use Modules\Services\Repositories\Interfaces\MilestoneServiceRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MilestoneService
{
    public function __construct(
        protected MilestoneRepositoryInterface $milestoneRepository,
        protected MilestoneServiceRepositoryInterface $milestoneServiceRepository,
        protected ServiceOfferOrderRepositoryInterface $orderRepository,
    ) {}

    public function listForProvider(ServiceProvider $provider, array $filters = []): LengthAwarePaginator
    {
        return $this->milestoneRepository->getByServiceProviderId($provider->id, $filters);
    }

    public function getById(int $id, int $serviceProviderId): ?Milestone
    {
        return $this->milestoneRepository->findById($id, $serviceProviderId);
    }

    public function create(ServiceProvider $provider, array $data): Milestone
    {
        return DB::transaction(function () use ($provider, $data) {
            $orderId = (int) $data['service_offer_order_id'];
            unset($data['service_offer_order_id']);

            $order = $this->orderRepository->findNegotiationOrderForProvider($provider->id, $orderId);

            if (! $order) {
                throw new HttpException(422, __('services::messages.milestones.negotiation_order_not_found'));
            }

            $services = $data['services'] ?? [];
            unset($data['services']);

            $data['service_provider_id'] = $provider->id;
            $data['service_offer_order_id'] = $order->id;
            $data['activation_status'] = $data['activation_status'] ?? false;
            $data = array_merge($data, $this->aggregatesFromServiceLines($services));

            $milestone = $this->milestoneRepository->create($data);

            if ($services !== []) {
                $this->milestoneServiceRepository->syncForMilestone($milestone, $services);
                $milestone->recalculateAggregates();
            }

            $milestone->refresh();

            $this->orderRepository->update([
                'orderable_type' => OrderableType::MilestoneTemplate->value,
                'orderable_id' => $milestone->id,
                'title' => $milestone->title,
                'price' => $milestone->total_budget,
                'delivery_time_minutes' => max(0, (int) $milestone->duration_days * 1440),
            ], $order);

            return $this->milestoneRepository->findById($milestone->id, $provider->id) ?? $milestone;
        });
    }

    public function update(Milestone $milestone, array $data): Milestone
    {
        return DB::transaction(function () use ($milestone, $data) {
            $services = $data['services'] ?? null;
            unset($data['services']);

            if (is_array($services)) {
                $data = array_merge($data, $this->aggregatesFromServiceLines($services));
            }

            $milestone = $this->milestoneRepository->update($milestone, $data);

            if (is_array($services)) {
                $this->milestoneServiceRepository->syncForMilestone($milestone, $services);
                $milestone->recalculateAggregates();
            }

            return $this->milestoneRepository->findById($milestone->id, $milestone->service_provider_id) ?? $milestone;
        });
    }

    public function delete(Milestone $milestone): void
    {
        $this->milestoneRepository->delete($milestone);
    }

    public function toggleActivation(Milestone $milestone): Milestone
    {
        if ($milestone->milestoneServices()->count() === 0) {
            throw new HttpException(422, __('services::messages.milestones.services_required_for_activation'));
        }

        return $this->milestoneRepository->toggleActivation($milestone);
    }

    public function addServiceLine(Milestone $milestone, array $data): MilestoneServiceLine
    {
        $count = $milestone->milestoneServices()->count();
        $data['sort_order'] = $data['sort_order'] ?? $count;

        return $this->milestoneServiceRepository->create($milestone, $data);
    }

    public function updateServiceLine(MilestoneServiceLine $line, array $data): MilestoneServiceLine
    {
        return $this->milestoneServiceRepository->update($line, $data);
    }

    public function deleteServiceLine(MilestoneServiceLine $line): void
    {
        $this->milestoneServiceRepository->delete($line);
    }

    public function findServiceLineOrFail(int $lineId, int $serviceProviderId): MilestoneServiceLine
    {
        $line = $this->milestoneServiceRepository->findById($lineId, $serviceProviderId);

        if (! $line) {
            throw new NotFoundHttpException(__('services::messages.milestones.service_line_not_found'));
        }

        return $line;
    }

    public function findMilestoneOrFail(int $id, int $serviceProviderId): Milestone
    {
        $milestone = $this->milestoneRepository->findTemplateById($id, $serviceProviderId);

        if (! $milestone) {
            throw new NotFoundHttpException(__('services::messages.milestones.not_found'));
        }

        return $milestone;
    }

    /**
     * @param  array<int, array<string, mixed>>  $services
     * @return array{duration_days: int, total_budget: float}
     */
    private function aggregatesFromServiceLines(array $services): array
    {
        $durationDays = 0;
        $totalBudget = 0.0;

        foreach ($services as $line) {
            $durationDays += (int) ($line['duration_days'] ?? 0);
            $totalBudget += (float) ($line['budget'] ?? 0);
        }

        return [
            'duration_days' => $durationDays,
            'total_budget' => $totalBudget,
        ];
    }
}
