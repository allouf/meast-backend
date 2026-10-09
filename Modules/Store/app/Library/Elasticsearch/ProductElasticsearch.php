<?php

namespace Modules\Store\Library\Elasticsearch;

use App\Library\Elasticsearch\Elasticsearch as BaseElasticsearch;
use Modules\Core\Services\LocationContextService;
use Modules\Store\Models\Product;

class ProductElasticsearch extends BaseElasticsearch
{
    protected string $index = 'products';
    /**
     * Create product index with basic mapping
     */
    public function createProductIndex()
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'store_id' => ['type' => 'integer'],
                    'category_id' => ['type' => 'integer'],
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
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'specifications' => ['type' => 'text', 'analyzer' => 'standard'],
                    'additional_features' => [
                        'type' => 'object',
                        'enabled' => true,
                        'dynamic' => true,
                        'properties' => []
                    ],
                    'price' => ['type' => 'double'],
                    'slug' => ['type' => 'keyword'],
                    'status' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'avg_rating' => ['type' => 'double'],
                    'number_of_buys' => ['type' => 'integer'],
                    'store_country_code' => ['type' => 'keyword'],
                    'store_city' => ['type' => 'keyword'],
                    'store_region' => ['type' => 'keyword'],
                ]
            ]
        ];
        return $this->createIndex($this->index, $mapping);
    }
    /**
     * Recreate product index (delete and create with new mapping)
     * WARNING: This will delete all existing data in the index
     */
    public function recreateProductIndex()
    {
        try {
            if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
                $this->deleteIndex($this->index);
            }
        } catch (\Exception $e) {
        }
        return $this->createProductIndex();
    }
    /**
     * Index product (create or update)
     */
    public function indexProduct(Product $product)
    {
        $data = [
            'store_id' => $product->store_id,
            'category_id' => $product->category_id,
            'name' => $product->name,
            'description' => $product->description ?? '',
            'specifications' => $product->specifications ?? '',
            'price' => (float) $product->price,
            'slug' => $product->slug,
            'status' => $product->status?->value ?? 'active',
            'created_at' => $product->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'avg_rating' => (float) ($product->avg_rating ?? 0),
            'number_of_buys' => (int) ($product->number_of_buys ?? 0),
        ];
        
        // Include store location fields for filtering
        if ($product->relationLoaded('store') && $product->store) {
            $data['store_country_code'] = $product->store->country_code ?? '';
            $data['store_city'] = $product->store->city ?? '';
            $data['store_region'] = $product->store->region ?? '';
        } elseif ($product->store_id) {
            // Load store if not already loaded
            $product->load('store');
            if ($product->store) {
                $data['store_country_code'] = $product->store->country_code ?? '';
                $data['store_city'] = $product->store->city ?? '';
                $data['store_region'] = $product->store->region ?? '';
            } else {
                $data['store_country_code'] = '';
                $data['store_city'] = '';
                $data['store_region'] = '';
            }
        } else {
            $data['store_country_code'] = '';
            $data['store_city'] = '';
            $data['store_region'] = '';
        }
        
        // Only include additional_features if it's a valid object/array
        $additionalFeatures = $this->normalizeAdditionalFeatures($product->additional_features);
        if ($additionalFeatures !== null) {
            $data['additional_features'] = $additionalFeatures;
        }
        
        return $this->indexDocument($this->index, $product->id, $data);
    }
    /**
     * Search products by keyword (basic)
     */
    public function searchProducts(?string $keyword = null)
    {
        $must = [];
        if ($keyword) {
            $must[] = [
                'multi_match' => [
                    'query' => $keyword,
                    'fields' => ['name^2', 'description', 'specifications', 'slug']
                ]
            ];
        }
        $query = $this->buildQuery($must);
        return $this->search($this->index, $query);
    }
    /**
     * Search products with filters and pagination
     */
    public function searchProductsWithFilters(array $filters)
    {
        $must = [];
        $filter = [];
        $should = [];
        $mustNot = [];
        if (!empty($filters['search'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['search'],
                    'fields' => ['name^3', 'description^2', 'specifications', 'slug'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO'
                ]
            ];
        }
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $filter[] = ['terms' => ['category_id' => array_map('intval', $filters['category_ids'])]];
        } elseif (!empty($filters['category_id'])) {
            $filter[] = ['term' => ['category_id' => (int)$filters['category_id']]];
        }
        if (!empty($filters['store_id'])) {
            $filter[] = ['term' => ['store_id' => (int)$filters['store_id']]];
        }
        
        // Auto-apply location filters from headers (only if location filtering is enabled)
        $locationService = app(LocationContextService::class);
        if ($locationService->isEnabled() && $locationService->hasLocation()) {
            $locationFilters = $locationService->getFilters();
            
            // Only apply if not explicitly provided in filters
            if (empty($filters['country_code']) && !empty($locationFilters['country_code'])) {
                $filter[] = ['term' => ['store_country_code' => $locationFilters['country_code']]];
            }
            if (empty($filters['city']) && !empty($locationFilters['city'])) {
                $filter[] = ['term' => ['store_city' => $locationFilters['city']]];
            }
            if (empty($filters['region']) && !empty($locationFilters['region'])) {
                $filter[] = ['term' => ['store_region' => $locationFilters['region']]];
            }
        }
        
        // Explicit location filters take precedence
        if (!empty($filters['country_code'])) {
            // Remove any existing country_code filter and add the explicit one
            $filter = array_values(array_filter($filter, function($item) {
                return !isset($item['term']['store_country_code']);
            }));
            $filter[] = ['term' => ['store_country_code' => $filters['country_code']]];
        }
        if (!empty($filters['city'])) {
            // Remove any existing city filter and add the explicit one
            $filter = array_values(array_filter($filter, function($item) {
                return !isset($item['term']['store_city']);
            }));
            $filter[] = ['term' => ['store_city' => $filters['city']]];
        }
        if (!empty($filters['region'])) {
            // Remove any existing region filter and add the explicit one
            $filter = array_values(array_filter($filter, function($item) {
                return !isset($item['wildcard']['store_region']) && !isset($item['term']['store_region']);
            }));
            $filter[] = ['wildcard' => ['store_region' => '*' . $filters['region'] . '*']];
        }
        
        $status = $filters['status'] ?? 'active';
        $filter[] = ['term' => ['status' => $status]];
        if (isset($filters['min_price'])) {
            $filter[] = [
                'range' => [
                    'price' => [
                        'gte' => (float)$filters['min_price']
                    ]
                ]
            ];
        }
        if (isset($filters['max_price'])) {
            $filter[] = [
                'range' => [
                    'price' => [
                        'lte' => (float)$filters['max_price']
                    ]
                ]
            ];
        }

        if (isset($filters['rating'])) {
            $rating = (float) $filters['rating'];
            $filter[] = [
                'range' => [
                    'avg_rating' => [
                        'gte' => $rating,
                        'lt' => $rating + 1,
                    ],
                ],
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
            $allowedSortFields = ['name', 'price', 'created_at', 'avg_rating'];
            if (in_array($sortBy, $allowedSortFields)) {
                $sortField = $sortBy === 'name' ? 'name.keyword' : $sortBy;
                $query['sort'] = [
                    [$sortField => ['order' => $sortOrder]]
                ];
            }
        } else {
            if (!empty($filters['search'])) {
            } else {
                $query['sort'] = [
                    ['created_at' => ['order' => 'desc']]
                ];
            }
        }
        return $this->search($this->index, $query);
    }

    /**
     * Normalize additional_features to always be a valid object or null
     */
    private function normalizeAdditionalFeatures($features)
    {
        if (is_null($features)) {
            return null;
        }
        
        if (is_array($features)) {
            // Convert array to object if it's an associative array
            if (empty($features)) {
                return null;
            }
            // If it's an associative array, convert to object
            if (array_keys($features) !== range(0, count($features) - 1)) {
                // Remove any null values from the array
                $features = array_filter($features, function($value) {
                    return $value !== null;
                });
                return empty($features) ? null : (object)$features;
            }
            // For indexed arrays, wrap in an object
            return (object)['items' => $features];
        }
        
        if (is_object($features)) {
            // Convert to array, filter nulls, convert back to object
            $array = (array)$features;
            $array = array_filter($array, function($value) {
                return $value !== null;
            });
            return empty($array) ? null : (object)$array;
        }
        
        // For any other type (string, etc.), wrap it
        return (object)['value' => $features];
    }
}
