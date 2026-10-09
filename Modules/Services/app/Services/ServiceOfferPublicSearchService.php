<?php

namespace Modules\Services\Services;

use Carbon\CarbonImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Services\Enums\BoostCampaignStatus;
use Modules\Services\Enums\ServiceOfferStatus;
use Modules\Services\Filters\ServiceOfferFilter;
use Modules\Services\Models\BoostCampaign;
use Modules\Services\Models\ServiceOffer;

/**
 * Public service browse (US‑P3): base relevance × bounded boost, sponsor-density & provider-soft guardrails, snapshot ordering.
 */
class ServiceOfferPublicSearchService
{
    private const MAX_RANK_POOL = 1500;

    private const EFFECTIVE_MULTIPLIER_CAP = 1.5;

    private const SPONSOR_WINDOW = 20;

    private const SPONSORS_MAX_IN_WINDOW = 2;

    private const PROVIDER_WINDOW = 12;

    private const PROVIDER_MAX_IN_WINDOW = 3;

    private const DEFAULT_RELATIONS = [
        'serviceCategory', 'plans', 'faqs', 'availableSlots', 'media', 'serviceProvider',
        'coverMedia', 'fastestPlan', 'reviews.user',
    ];

    /**
     * @param  array<string, mixed>  $filters Same keys as public listing endpoint (+ optional viewer_country_code).
     * @return array{
     *   paginator: LengthAwarePaginator<ServiceOffer>,
     *   ranking_snapshot_at: string,
     *   ranking_pool_limited: bool
     * }
     */
    public function paginate(array $filters = []): array
    {
        $snapshot = CarbonImmutable::now();
        $snapshotStr = $snapshot->toIso8601String();

        $perPage = max(1, (int) ($filters['per_page'] ?? 15));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $baseQuery = ServiceOffer::query()
            ->where('is_active', true)
            ->where('status', ServiceOfferStatus::Active)
            ->with(array_merge(self::DEFAULT_RELATIONS, ['serviceProvider']))
            ->withAvg('reviews', 'rating');

        $filtered = (new ServiceOfferFilter($filters))->apply(clone $baseQuery, false);
        $total = (clone $filtered)->count();

        if ($total === 0) {
            return [
                'paginator' => new LengthAwarePaginator(
                    collect([]),
                    0,
                    $perPage,
                    $page,
                    ['path' => request()->url(), 'query' => request()->query()],
                ),
                'ranking_snapshot_at' => $snapshotStr,
                'ranking_pool_limited' => false,
            ];
        }

        $poolLimited = $total > self::MAX_RANK_POOL;
        $selectQuery = (clone $filtered)->select('service_offers.id');
        if ($poolLimited) {
            $selectQuery->orderByDesc('service_offers.updated_at')
                ->orderByDesc('service_offers.id')
                ->limit(self::MAX_RANK_POOL);
        }

        $ids = $selectQuery->pluck('service_offers.id')->all();

        $offersById = ServiceOffer::query()
            ->with(array_merge(self::DEFAULT_RELATIONS, ['serviceProvider']))
            ->withAvg('reviews', 'rating')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        /** @var Collection<int, BoostCampaign> $boostAtSnapshot */
        $boostAtSnapshot = BoostCampaign::query()
            ->whereIn('service_offer_id', $ids)
            ->where('status', BoostCampaignStatus::Active)
            ->where('start_at', '<=', $snapshot)
            ->where('end_at', '>=', $snapshot)
            ->with('plan')
            ->get()
            ->keyBy('service_offer_id');

        /** @var list<array<string, mixed>> $rows */
        $rows = [];
        foreach ($ids as $id) {
            $offer = $offersById->get($id);
            if (! $offer) {
                continue;
            }
            $boost = $boostAtSnapshot->get($id);

            [$baseScore, ] = $this->computeBaseRelevance($offer, $filters, $snapshot);
            $rawMult = $boost && $boost->plan ? $boost->plan->searchRankMultiplier() : 1.0;
            $boundedMult = min(max($rawMult, 1.0), self::EFFECTIVE_MULTIPLIER_CAP);
            $hasBoost = $boost !== null && $boundedMult > 1.00001;

            $finalScore = $baseScore * ($hasBoost ? $boundedMult : 1.0);

            $rows[] = [
                'id' => (int) $offer->id,
                'offer' => $offer,
                'boost' => $boost,
                'base_relevance_score' => $baseScore,
                'boost_multiplier' => $boundedMult,
                'has_boost' => $hasBoost,
                'final_rank_score' => $finalScore,
            ];
        }

        $sortKey = (string) ($filters['sort'] ?? 'newest');

        /** @phpstan-ignore-next-line */
        $sortedRows = Collection::make($rows)
            ->sort(function (array $a, array $b) use ($sortKey): int {
                $p = $b['final_rank_score'] <=> $a['final_rank_score'];
                if ($p !== 0) {
                    return $p;
                }

                return $this->tieBreakOffers($a['offer'], $b['offer'], $sortKey);
            })
            ->values();

        $guardedRows = $this->enforceGuardrails($sortedRows, $sortKey);
        /** @phpstan-ignore-next-line */
        $flattenedOffers = Collection::make($guardedRows)
            ->pluck('offer')
            ->values();

        /** @phpstan-ignore-next-line */
        $paginator = new LengthAwarePaginator(
            $flattenedOffers->forPage($page, $perPage)->values()->all(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        $metaById = [];
        foreach ($guardedRows as $gr) {
            $metaById[$gr['offer']->id] = [
                'ranking_boost_applied' => (bool) $gr['ranking_boost_applied'],
                'base_relevance_score' => (float) $gr['base_relevance_score'],
                'boost_multiplier_effective' => (float) $gr['boost_multiplier_effective'],
            ];
        }

        foreach ($paginator->items() as $offer) {
            $boost = $boostAtSnapshot->get($offer->id);
            $offer->setRelation('activeBoostCampaign', $boost ?: null);
            $m = $metaById[$offer->id] ?? ['ranking_boost_applied' => false, 'base_relevance_score' => 0.0, 'boost_multiplier_effective' => 1.0];
            $offer->setAttribute('_ranking_boost_applied', $m['ranking_boost_applied']);
            $offer->setAttribute('_base_relevance_score', $m['base_relevance_score']);
            $offer->setAttribute('_boost_multiplier_effective', $m['boost_multiplier_effective']);
        }

        return [
            'paginator' => $paginator,
            'ranking_snapshot_at' => $snapshotStr,
            'ranking_pool_limited' => $poolLimited,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sortedRows
     * @return Collection<int, array<string, mixed>>
     */
    protected function enforceGuardrails(Collection $sortedRows, string $sortKey): Collection
    {
        /** @phpstan-ignore-next-line */
        $out = collect();
        /** @var list<bool> $sponsorWindow */
        $sponsorWindow = [];

        foreach ($sortedRows as $row) {
            /** @phpstan-ignore-next-line */
            $offer = $row['offer'];
            $providerId = (int) $offer->service_provider_id;

            $providerCrowded = $this->recentProviderMatches($out, $providerId) >= self::PROVIDER_MAX_IN_WINDOW;

            if (! $row['has_boost']) {
                $marked = [...$row, 'ranking_boost_applied' => false, 'boost_multiplier_effective' => 1.0];
                /** @phpstan-ignore-next-line */
                $out->push($marked);
                $this->pushSponsorWindow($sponsorWindow, false);

                continue;
            }

            $boostSlotAvailable = ! $providerCrowded
                && $this->boostSlotAvailableInWindow($sponsorWindow);

            if ($boostSlotAvailable) {
                /** @phpstan-ignore-next-line */
                $marked = [
                    ...$row,
                    'ranking_boost_applied' => true,
                    'boost_multiplier_effective' => (float) $row['boost_multiplier'],
                ];
                /** @phpstan-ignore-next-line */
                $out->push($marked);
                $this->pushSponsorWindow($sponsorWindow, true);

                continue;
            }

            $organicScore = (float) $row['base_relevance_score'];
            /** @phpstan-ignore-next-line */
            $marked = [
                ...$row,
                'final_rank_score' => $organicScore,
                'ranking_boost_applied' => false,
                'boost_multiplier_effective' => 1.0,
            ];
            /** @phpstan-ignore-next-line */
            $out->push($marked);
            $this->pushSponsorWindow($sponsorWindow, false);
        }

        /** @phpstan-ignore-next-line */
        return $out->sort(function (array $a, array $b) use ($sortKey): int {
            $d = $b['final_rank_score'] <=> $a['final_rank_score'];
            if ($d !== 0) {
                return $d;
            }

            return $this->tieBreakOffers($a['offer'], $b['offer'], $sortKey);
        })->values();
    }

    protected function tieBreakOffers(ServiceOffer $a, ServiceOffer $b, string $sort): int
    {
        return match ($sort) {
            'oldest' => $a->created_at <=> $b->created_at,
            'lowest_price' => $this->minPlanPrice($a) <=> $this->minPlanPrice($b),
            'highest_price' => $this->minPlanPrice($b) <=> $this->minPlanPrice($a),
            'lowest_rate' => (($a->reviews_avg_rating ?? 0) <=> ($b->reviews_avg_rating ?? 0))
                ?: (($b->created_at <=> $a->created_at)),
            'highest_rate' => (($b->reviews_avg_rating ?? 0) <=> ($a->reviews_avg_rating ?? 0))
                ?: (($b->created_at <=> $a->created_at)),
            'sort_order_asc' => ($a->sort_order <=> $b->sort_order) ?: ($b->created_at <=> $a->created_at),
            'sort_order_desc' => ($b->sort_order <=> $a->sort_order) ?: ($b->created_at <=> $a->created_at),
            'title_asc' => strcmp((string) $a->title, (string) $b->title) ?: ($a->id <=> $b->id),
            'title_desc' => strcmp((string) $b->title, (string) $a->title) ?: ($a->id <=> $b->id),
            default => ($b->created_at <=> $a->created_at) ?: ($b->id <=> $a->id),
        };
    }

    protected function minPlanPrice(ServiceOffer $offer): float
    {
        $offer->loadMissing('plans');
        if ($offer->relationLoaded('plans') && $offer->plans->isNotEmpty()) {
            return (float) $offer->plans->min('price');
        }

        return PHP_FLOAT_MAX;
    }

    /** @phpstan-ignore-next-line */
    protected function recentProviderMatches(Collection $out, int $providerId): int
    {
        /** @phpstan-ignore-next-line */
        $slice = $out->slice(max(0, $out->count() - self::PROVIDER_WINDOW + 1))->values();

        $n = 0;
        foreach ($slice as $r) {
            if ((int) $r['offer']->service_provider_id === $providerId) {
                $n++;
            }
        }

        return $n;
    }

    /** @param  list<bool>  $window */
    protected function boostSlotAvailableInWindow(array $window): bool
    {
        if ($window === []) {
            return true;
        }

        /** @phpstan-ignore-next-line */
        $tail = array_slice($window, -self::SPONSOR_WINDOW + 1);
        $used = array_sum(array_map(static fn ($b) => $b ? 1 : 0, $tail));

        return $used < self::SPONSORS_MAX_IN_WINDOW;
    }

    /** @param  list<bool>  $window */
    protected function pushSponsorWindow(array &$window, bool $usedBoostRanking): void
    {
        $window[] = $usedBoostRanking;
        if (count($window) > self::SPONSOR_WINDOW) {
            array_shift($window);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: float, 1: bool}
     */
    protected function computeBaseRelevance(ServiceOffer $offer, array $filters, CarbonImmutable $snapshot): array
    {
        $score = 1.0;
        $locationBonusApplied = false;
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $q = Str::lower($search);
            $title = Str::lower((string) $offer->title);
            $desc = Str::lower((string) ($offer->description ?? ''));
            if (str_contains($title, $q)) {
                $score += 14.0;
            } elseif (str_contains($desc, $q)) {
                $score += 8.0;
            } elseif (preg_match('/' . preg_quote($q, '/') . '/u', $title.' '.$desc) === 1) {
                $score += 6.0;
            } else {
                $tokens = preg_split('/\s+/u', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $hits = 0;
                foreach ($tokens as $tok) {
                    if (strlen($tok) >= 3 && str_contains($title, $tok)) {
                        $hits++;
                    }
                }
                $score += min(5.0, $hits * 1.25);
                if ($hits === 0) {
                    $score *= 0.35;
                }
            }
        }

        $catId = $filters['category_id'] ?? $filters['service_category_id'] ?? null;
        if ($catId !== null && (int) $offer->service_category_id === (int) $catId) {
            $score += 5.0;
        }

        $offer->loadMissing('serviceProvider');
        $viewerCc = isset($filters['viewer_country_code'])
            ? strtoupper(trim((string) $filters['viewer_country_code']))
            : '';
        $providerCc = strtoupper(trim((string) ($offer->serviceProvider->country_code ?? '')));
        if ($viewerCc !== '' && $providerCc !== '') {
            if ($providerCc === $viewerCc) {
                $score += 4.5;
                $locationBonusApplied = true;
            } else {
                $score *= 0.55;
            }
        }

        $rating = isset($offer->reviews_avg_rating) ? (float) $offer->reviews_avg_rating : 0.0;
        $score *= 1 + min(4.9, max(0, $rating)) * 0.08;

        $ref = $offer->updated_at ?? $offer->created_at;
        $freshDays = $ref ? max(0, $ref->diffInDays($snapshot)) : 365;
        $freshnessBoost = exp(-min(730, $freshDays) / 420.0);
        $score *= 0.9 + ($freshnessBoost * 0.25);

        return [max(0.01, $score), $locationBonusApplied];
    }
}
