<?php

namespace Modules\Store\Library\Elasticsearch;

use App\Library\Elasticsearch\Elasticsearch as BaseElasticsearch;
use Modules\Core\Services\LocationContextService;
use Modules\Store\Models\Store;

class StoreElasticsearch extends BaseElasticsearch
{
    protected string $index = 'stores';

    /**
     * Create store index with basic mapping
     */
    public function createStoreIndex()
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'user_id' => ['type' => 'integer'],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    'slug' => ['type' => 'keyword'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'address' => ['type' => 'text', 'analyzer' => 'standard'],
                    'city' => ['type' => 'keyword'],
                    'country_code' => ['type' => 'keyword'],
                    'postal_code' => ['type' => 'keyword'],
                    'region' => ['type' => 'keyword'],
                    'latitude' => ['type' => 'double'],
                    'longitude' => ['type' => 'double'],
                    'website' => ['type' => 'keyword'],
                    'status' => ['type' => 'keyword'],
                    'is_active' => ['type' => 'boolean'],
                    'is_returnable' => ['type' => 'boolean'],
                    'is_freezone' => ['type' => 'boolean'],
                    'rating' => ['type' => 'double'],
                    'created_at' => ['type' => 'date'],
                    'phones' => ['type' => 'keyword'],
                    'emails' => ['type' => 'keyword'],
                ]
            ]
        ];
        return $this->createIndex($this->index, $mapping);
    }

    /**
     * Recreate store index (delete and create with new mapping)
     * WARNING: This will delete all existing data in the index
     */
    public function recreateStoreIndex()
    {
        try {
            if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
                $this->deleteIndex($this->index);
            }
        } catch (\Exception $e) {
        }
        return $this->createStoreIndex();
    }

    /**
     * Index store (create or update)
     */
    public function indexStore(Store $store)
    {
        $data = [
            'user_id' => $store->user_id,
            'name' => $store->name,
            'slug' => $store->slug,
            'description' => $store->description ?? '',
            'address' => $store->address ?? '',
            'city' => $store->city ?? '',
            'country_code' => $store->country_code ?? '',
            'postal_code' => $store->postal_code ?? '',
            'region' => $store->region ?? '',
            'latitude' => $store->latitude !== null ? (float) $store->latitude : null,
            'longitude' => $store->longitude !== null ? (float) $store->longitude : null,
            'website' => $store->website ?? '',
            'status' => $store->status?->value ?? 'active',
            'is_active' => (bool) ($store->is_active ?? true),
            'is_returnable' => (bool) ($store->is_returnable ?? false),
            'is_freezone' => (bool) ($store->is_freezone ?? false),
            'rating' => (float) ($store->rating ?? 0),
            'created_at' => $store->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'phones' => is_array($store->phones) ? $store->phones : [],
            'emails' => is_array($store->emails) ? $store->emails : [],
        ];
        return $this->indexDocument($this->index, $store->id, $data);
    }

    /**
     * Search stores by keyword (basic)
     */
    public function searchStores(?string $keyword = null)
    {
        $must = [];
        if ($keyword) {
            $must[] = [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => ['name^2', 'description', 'address', 'slug']
                ]
            ];
        }
        $query = $this->buildQuery($must);
        return $this->search($this->index, $query);
    }

    /**
     * Search stores with filters and pagination
     */
    public function searchStoresWithFilters(array $filters)
    {
        $must = [];
        $filter = [];
        $should = [];
        $mustNot = [];

        if (!empty($filters['search'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['search'],
                    'fields' => ['name^3', 'description^2', 'address', 'slug'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ];
        }

        if (!empty($filters['user_id'])) {
            $filter[] = ['term' => ['user_id' => (int)$filters['user_id']]];
        }

        // Auto-apply location filters from headers (only if location filtering is enabled)
        $locationService = app(LocationContextService::class);
        if ($locationService->isEnabled() && $locationService->hasLocation()) {
            $locationFilters = $locationService->getFilters();

            // Only apply if not explicitly provided in filters
            if (empty($filters['country_code']) && !empty($locationFilters['country_code'])) {
                $filter[] = ['term' => ['country_code' => $locationFilters['country_code']]];
            }
            if (empty($filters['city']) && !empty($locationFilters['city'])) {
                $filter[] = ['term' => ['city' => $locationFilters['city']]];
            }
            if (empty($filters['region']) && !empty($locationFilters['region'])) {
                $filter[] = ['term' => ['region' => $locationFilters['region']]];
            }
        }

        // Explicit location filters take precedence
        if (!empty($filters['country_code'])) {
            // Remove any existing country_code filter and add the explicit one
            $filter = array_values(array_filter($filter, function ($item) {
                return !isset($item['term']['country_code']);
            }));
            $filter[] = ['term' => ['country_code' => $filters['country_code']]];
        }
        if (!empty($filters['city'])) {
            // Remove any existing city filter and add the explicit one
            $filter = array_values(array_filter($filter, function ($item) {
                return !isset($item['term']['city']);
            }));
            $filter[] = ['term' => ['city' => $filters['city']]];
        }
        if (!empty($filters['region'])) {
            // Remove any existing region filter and add the explicit one
            $filter = array_values(array_filter($filter, function ($item) {
                return !isset($item['wildcard']['region']) && !isset($item['term']['region']);
            }));
            $filter[] = ['wildcard' => ['region' => '*' . $filters['region'] . '*']];
        }

        if (isset($filters['is_active'])) {
            $filter[] = ['term' => ['is_active' => filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN)]];
        }

        if (isset($filters['is_returnable'])) {
            $filter[] = ['term' => ['is_returnable' => filter_var($filters['is_returnable'], FILTER_VALIDATE_BOOLEAN)]];
        }

        if (isset($filters['is_freezone'])) {
            $filter[] = ['term' => ['is_freezone' => filter_var($filters['is_freezone'], FILTER_VALIDATE_BOOLEAN)]];
        }

        $status = $filters['status'] ?? 'active';
        $filter[] = ['term' => ['status' => $status]];

        if (isset($filters['min_rating'])) {
            $filter[] = [
                'range' => [
                    'rating' => [
                        'gte' => (float)$filters['min_rating']
                    ]
                ]
            ];
        }

        if (isset($filters['max_rating'])) {
            $filter[] = [
                'range' => [
                    'rating' => [
                        'lte' => (float)$filters['max_rating']
                    ]
                ]
            ];
        }

        $query = $this->buildQuery($must, $filter, $should, $mustNot);
        $page = (int)($filters['page'] ?? 1);
        $perPage = (int)($filters['per_page'] ?? 15);
        $from = ($page - 1) * $perPage;
        $query['from'] = $from;
        $query['size'] = $perPage;

        if (!empty($filters['sort_by'])) {
            $sortBy = $filters['sort_by'];
            $sortOrder = $filters['sort_order'] ?? 'desc';
            $allowedSortFields = ['name', 'rating', 'created_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $sortField = $sortBy === 'name' ? 'name.keyword' : $sortBy;
                $query['sort'] = [
                    [$sortField => ['order' => $sortOrder]]
                ];
            }
        } else {
            if (!empty($filters['search'])) {
                // Use relevance score when searching
            } else {
                $query['sort'] = [
                    ['created_at' => ['order' => 'desc']]
                ];
            }
        }

        return $this->search($this->index, $query);
    }
}
