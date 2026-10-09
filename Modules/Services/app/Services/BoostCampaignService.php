<?php

namespace Modules\Services\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\BoostCampaignStatus;
use Modules\Services\Models\BoostCampaign;
use Modules\Services\Models\BoostPlan;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Repositories\Interfaces\BoostCampaignLogRepositoryInterface;
use Modules\Services\Repositories\Interfaces\BoostCampaignRepositoryInterface;
use Modules\Services\Repositories\Interfaces\BoostPlanRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BoostCampaignService
{
    /** @var list<int> */
    private const MVP_DURATION_DAYS = [3, 7, 30];

    public function __construct(
        protected BoostCampaignRepositoryInterface $campaignRepository,
        protected BoostCampaignLogRepositoryInterface $logRepository,
        protected BoostPlanRepositoryInterface $boostPlanRepository,
    ) {}

    /**
     * Create ACTIVE boost, or extend an existing ACTIVE boost on the same listing (stacking rule).
     * `amount` / `currency` record the plan price for reporting; payment is handled outside this module.
     *
     * @param  array{service_offer_id: int|string, boost_plan_id: int|string}  $data
     */
    public function createForProvider(ServiceProvider $provider, array $data): BoostCampaign
    {
        return DB::transaction(function () use ($provider, $data) {
            $offer = ServiceOffer::query()
                ->with('serviceCategory')
                ->whereKey((int) $data['service_offer_id'])
                ->where('service_provider_id', $provider->id)
                ->lockForUpdate()
                ->first();

            if (! $offer) {
                throw new HttpException(404, __('services::messages.service_offers.not_found'));
            }

            $this->assertOfferBoostable($offer);

            $plan = $this->boostPlanRepository->findById((int) $data['boost_plan_id']);
            if (! $plan || ! $plan->is_active) {
                throw new HttpException(422, __('services::messages.boost_plans.not_found'));
            }

            $durationDays = (int) $plan->duration_days;
            if (! in_array($durationDays, self::MVP_DURATION_DAYS, true)) {
                throw new HttpException(422, __('services::messages.boost_campaigns.invalid_plan_duration'));
            }

            $plan->loadMissing('variants');
            [$amountStr, $currency] = $this->resolvePrice($provider, $plan);

            $targetingScope = $this->buildTargetingScope($offer, $provider, $plan);

            $existing = $this->campaignRepository->lockActiveWindowForOfferAndProvider($offer->id, $provider->id);

            $now = now();

            if ($existing) {
                $base = $existing->end_at->greaterThan($now) ? $existing->end_at->copy() : $now->copy();
                $newEnd = $base->addDays($durationDays);
                $totalPaid = round((float) $existing->amount + (float) $amountStr, 2);

                $this->campaignRepository->update([
                    'end_at' => $newEnd,
                    'amount' => $totalPaid,
                    'targeting_scope' => $targetingScope,
                    'boost_plan_id' => $plan->id,
                ], $existing);

                return $existing->fresh(['offer', 'provider', 'plan']);
            }

            return $this->campaignRepository->create([
                'service_offer_id' => $offer->id,
                'provider_id' => $provider->id,
                'boost_plan_id' => $plan->id,
                'start_at' => $now,
                'end_at' => $now->copy()->addDays($durationDays),
                'status' => BoostCampaignStatus::Active,
                'targeting_scope' => $targetingScope,
                'amount' => $amountStr,
                'currency' => $currency,
            ]);
        });
    }

    /**
     * When a listing stops being publicly boost-eligible (moderation / pause / rejection / draft),
     * end active in-window boosts (no wallet movement here).
     */
    public function terminateActiveBoostsForOffer(ServiceOffer $offer, string $traceReason): void
    {
        DB::transaction(function () use ($offer, $traceReason) {
            $campaigns = $this->campaignRepository->lockActiveCampaignsForServiceOffer($offer->id);

            foreach ($campaigns as $campaign) {
                $refund = $this->computeProRataRefund($campaign);

                $this->campaignRepository->update([
                    'status' => BoostCampaignStatus::Terminated,
                    'terminated_at' => now(),
                ], $campaign);

                $this->logRepository->create([
                    'boost_campaign_id' => $campaign->id,
                    'action' => 'TERMINATED_MODERATION',
                    'admin_id' => null,
                    'reason' => $traceReason,
                    'meta' => [
                        'notional_refund_amount' => $refund,
                        'service_offer_id' => $offer->id,
                    ],
                ]);
            }
        });
    }

    public function listForProvider(ServiceProvider $provider, array $filters = []): LengthAwarePaginator
    {
        return $this->campaignRepository->paginatedForProvider($provider->id, $filters);
    }

    /** @param  array<string, mixed>  $filters */
    public function listCampaignsAdmin(array $filters = []): LengthAwarePaginator
    {
        return $this->campaignRepository->paginatedForAdmin($filters);
    }

    public function findCampaignAdmin(int $id): BoostCampaign
    {
        return $this->campaignRepository->findByIdWithAdminRelations($id);
    }

    public function pauseCampaign(int $id, int $adminId, ?string $reason = null): BoostCampaign
    {
        DB::beginTransaction();

        try {
            $campaign = $this->campaignRepository->findById($id);

            if ($campaign->status !== BoostCampaignStatus::Active) {
                throw new HttpException(422, 'Only active campaigns can be paused');
            }

            $this->campaignRepository->update(['status' => BoostCampaignStatus::Paused], $campaign);

            $this->logRepository->create([
                'boost_campaign_id' => $campaign->id,
                'action' => 'PAUSED',
                'admin_id' => $adminId,
                'reason' => $reason,
            ]);

            DB::commit();

            return $campaign->fresh(['offer', 'provider', 'plan']);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** Terminate campaign; `refund` in response is informational (pro‑rata of recorded amount) only. */
    public function terminateCampaign(int $id, int $adminId, ?string $reason = null): array
    {
        DB::beginTransaction();

        try {
            $campaign = $this->campaignRepository->findById($id);

            if (! in_array($campaign->status, [BoostCampaignStatus::Active, BoostCampaignStatus::Paused], true)) {
                throw new HttpException(422, 'Campaign cannot be terminated');
            }

            $refundStr = $this->computeProRataRefund($campaign);

            $this->campaignRepository->update([
                'status' => BoostCampaignStatus::Terminated,
                'terminated_at' => now(),
            ], $campaign);

            $this->logRepository->create([
                'boost_campaign_id' => $campaign->id,
                'action' => 'TERMINATED',
                'admin_id' => $adminId,
                'reason' => $reason,
                'meta' => [
                    'notional_refund_amount' => $refundStr,
                ],
            ]);

            DB::commit();

            return [
                'campaign' => $campaign->fresh(['offer', 'provider', 'plan']),
                'notional_refund' => (float) $refundStr,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function assertOfferBoostable(ServiceOffer $offer): void
    {
        if (! $offer->isEligibleForPaidBoostPurchase()) {
            throw new HttpException(422, __('services::messages.boost_campaigns.offer_not_boostable'));
        }

        $offer->loadMissing('serviceCategory');
        if ($offer->serviceCategory && $offer->serviceCategory->boost_prohibited) {
            throw new HttpException(422, __('services::messages.boost_campaigns.category_boost_prohibited'));
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function resolvePrice(ServiceProvider $provider, BoostPlan $plan): array
    {
        $amount = $plan->base_price;
        $currency = $plan->currency;

        $variant = $plan->variants->firstWhere('country_code', $provider->country_code);
        if ($variant && $variant->is_active) {
            $amount = $variant->price;
            $currency = $variant->currency;
        }

        return [number_format((float) $amount, 2, '.', ''), (string) $currency];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildTargetingScope(ServiceOffer $offer, ServiceProvider $provider, BoostPlan $plan): array
    {
        return [
            'version' => 1,
            'placement' => [
                'search_rank_multiplier' => $plan->searchRankMultiplier(),
                'show_sponsored_badge' => $plan->sponsoredBadgeEnabled(),
            ],
            'audience' => [
                'same_category_as_listing' => true,
                'service_category_id' => $offer->service_category_id,
                'geo' => [
                    'country_code' => $provider->country_code,
                    'city_or_region' => $provider->region,
                ],
            ],
        ];
    }

    protected function computeProRataRefund(BoostCampaign $campaign): string
    {
        $remainingMinutes = now()->diffInMinutes($campaign->end_at, false);
        if ($remainingMinutes <= 0) {
            return '0';
        }

        $totalMinutes = max(1, $campaign->start_at->diffInMinutes($campaign->end_at));
        $refundRatio = $remainingMinutes / $totalMinutes;

        return number_format(round(((float) $campaign->amount) * $refundRatio, 2), 2, '.', '');
    }
}
