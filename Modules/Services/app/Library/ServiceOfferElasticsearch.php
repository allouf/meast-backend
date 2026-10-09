<?php

namespace Modules\Services\Library;

use App\Library\Elasticsearch\Elasticsearch;
use Modules\Services\Models\ServiceOffer;

class ServiceOfferElasticsearch extends Elasticsearch
{
    protected string $index = 'service_offers';

    public function createServiceOfferIndex(): array
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'service_provider_id' => ['type' => 'integer'],
                    'service_category_id' => ['type' => 'integer'],
                    'title' => ['type' => 'text', 'analyzer' => 'standard', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'type' => ['type' => 'keyword'],
                    'delivery_model' => ['type' => 'keyword'],
                    'status' => ['type' => 'keyword'],
                    'is_active' => ['type' => 'boolean'],
                    'sort_order' => ['type' => 'integer'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                ]
            ]
        ];
        return $this->createIndex($this->index, $mapping)->asArray();
    }

    public function indexDocumentFromModel(ServiceOffer $offer): array
    {
        $data = [
            'service_provider_id' => (int) $offer->service_provider_id,
            'service_category_id' => (int) $offer->service_category_id,
            'title' => $offer->title ?? '',
            'description' => $offer->description ?? '',
            'type' => $offer->type?->value ?? (string) $offer->getRawOriginal('type'),
            'delivery_model' => $offer->delivery_model?->value ?? (string) $offer->getRawOriginal('delivery_model'),
            'status' => $offer->status?->value ?? (string) $offer->getRawOriginal('status'),
            'is_active' => (bool) $offer->is_active,
            'sort_order' => (int) ($offer->sort_order ?? 0),
            'created_at' => $offer->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'updated_at' => $offer->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
        return $this->indexDocument($this->index, $offer->id, $data)->asArray();
    }

    /**
     * Search service offers with filters (provider-scoped).
     *
     * @param int $serviceProviderId provider scope
     * @param array<string, mixed> $filters
     * @return array
     */
    public function searchWithFilters(int $serviceProviderId, array $filters): array
    {
        $must = [];
        $filter = [];
        $filter[] = ['term' => ['service_provider_id' => $serviceProviderId]];

        if (! empty($filters['search'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['search'],
                    'fields' => ['title^3', 'description^2'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ];
        }
        if ($this->filled($filters, 'category_id')) {
            $filter[] = ['term' => ['service_category_id' => (int) $filters['category_id']]];
        }
        if ($this->filled($filters, 'service_category_id')) {
            $filter[] = ['term' => ['service_category_id' => (int) $filters['service_category_id']]];
        }
        if ($this->filled($filters, 'delivery_model')) {
            $filter[] = ['term' => ['delivery_model' => $filters['delivery_model']]];
        }
        if ($this->filled($filters, 'service_date_from')) {
            $filter[] = ['range' => ['created_at' => ['gte' => $filters['service_date_from']]]];
        }
        if ($this->filled($filters, 'service_date_to')) {
            $filter[] = ['range' => ['created_at' => ['lte' => $filters['service_date_to']]]];
        }
        if ($this->filled($filters, 'service_type')) {
            $filter[] = ['term' => ['type' => $filters['service_type']]];
        }
        if ($this->filled($filters, 'type')) {
            $filter[] = ['term' => ['type' => $filters['type']]];
        }
        if ($this->filled($filters, 'status')) {
            $filter[] = ['term' => ['status' => $filters['status']]];
        }
        if (isset($filters['is_active'])) {
            $filter[] = ['term' => ['is_active' => (bool) $filters['is_active']]];
        }

        $query = $this->buildQuery($must, $filter, [], []);
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $query['from'] = ($page - 1) * $perPage;
        $query['size'] = $perPage;
        $query['sort'] = $this->buildSort($filters['sort'] ?? 'newest');

        return $this->search($this->index, $query)->asArray();
    }

    private function filled(array $filters, string $key): bool
    {
        $v = $filters[$key] ?? null;
        return $v !== null && $v !== '';
    }

    protected function buildSort(string $sort): array
    {
        $sortMap = [
            'newest' => [['created_at' => ['order' => 'desc']]],
            'oldest' => [['created_at' => ['order' => 'asc']]],
            'sort_order_asc' => [['sort_order' => ['order' => 'asc']], ['created_at' => ['order' => 'desc']]],
            'sort_order_desc' => [['sort_order' => ['order' => 'desc']], ['created_at' => ['order' => 'desc']]],
            'title_asc' => [['title.keyword' => ['order' => 'asc']], ['id' => ['order' => 'asc']]],
            'title_desc' => [['title.keyword' => ['order' => 'desc']], ['id' => ['order' => 'desc']]],
        ];
        return $sortMap[$sort] ?? $sortMap['newest'];
    }

    public function deleteDocumentById(int $id): void
    {
        $this->deleteDocument($this->index, $id, true);
    }
}
