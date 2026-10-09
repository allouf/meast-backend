<?php

namespace Modules\Advertisement\Library;

use App\Library\Elasticsearch\Elasticsearch;
use Modules\Core\Services\LocationContextService;
use Modules\Advertisement\Models\Advertisement;

class AdvertisementElasticsearch extends Elasticsearch
{
    protected string $index = 'advertisements';

    public function createAdvertisementIndex()
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'user_id' => ['type' => 'integer'],
                    'advertisement_category_id' => ['type' => 'integer'],
                    'advertisement_subcategory_id' => ['type' => 'integer'],
                    'title' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]],
                    ],
                    'slug' => ['type' => 'keyword'],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'price' => ['type' => 'double'],
                    'condition' => ['type' => 'keyword'],
                    'status' => ['type' => 'keyword'],
                    'country_code' => ['type' => 'keyword'],
                    'region' => ['type' => 'keyword'],
                    'views_count' => ['type' => 'integer'],
                    'published_at' => ['type' => 'date'],
                    'created_at' => ['type' => 'date'],
                    'has_images' => ['type' => 'boolean'],
                    'is_free' => ['type' => 'boolean'],
                    'is_price_negotiable' => ['type' => 'boolean'],
                ],
            ],
        ];
        return $this->createIndex($this->index, $mapping);
    }

    public function recreateAdvertisementIndex()
    {
        try {
            if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
                $this->deleteIndex($this->index);
            }
        } catch (\Exception $e) {
            // ignore
        }
        return $this->createAdvertisementIndex();
    }

    public function indexAdvertisement(Advertisement $advertisement)
    {
        $data = [
            'user_id' => $advertisement->user_id,
            'advertisement_category_id' => $advertisement->advertisement_category_id ?? 0,
            'advertisement_subcategory_id' => $advertisement->advertisement_subcategory_id ?? 0,
            'title' => $advertisement->title ?? '',
            'slug' => $advertisement->slug ?? '',
            'description' => $advertisement->description ?? '',
            'price' => $advertisement->price !== null ? (float) $advertisement->price : null,
            'condition' => $advertisement->condition ?? 'used',
            'status' => $advertisement->status ?? 'active',
            'country_code' => $advertisement->country_code ?? '',
            'region' => $advertisement->region ?? '',
            'views_count' => (int) ($advertisement->views_count ?? 0),
            'published_at' => $advertisement->published_at?->toIso8601String() ?? null,
            'created_at' => $advertisement->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'has_images' => !empty($advertisement->images) && is_array($advertisement->images),
            'is_free' => (bool) ($advertisement->is_free ?? false),
            'is_price_negotiable' => (bool) ($advertisement->is_price_negotiable ?? false),
        ];
        return $this->indexDocument($this->index, $advertisement->id, $data);
    }

    public function searchAdvertisementsWithFilters(array $filters)
    {
        $must = [];
        $filter = [];
        $should = [];
        $mustNot = [];

        if (!empty($filters['user_id'])) {
            $filter[] = ['term' => ['user_id' => (int) $filters['user_id']]];
        }

        if (!empty($filters['search'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['search'],
                    'fields' => ['title^3', 'description^2', 'slug'],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                ],
            ];
        }

        if (!empty($filters['category_id'])) {
            $filter[] = ['term' => ['advertisement_category_id' => (int) $filters['category_id']]];
        }
        if (!empty($filters['subcategory_id'])) {
            $filter[] = ['term' => ['advertisement_subcategory_id' => (int) $filters['subcategory_id']]];
        }
        if (!empty($filters['condition'])) {
            $filter[] = ['term' => ['condition' => $filters['condition']]];
        }

        $locationService = app(LocationContextService::class);
        if ($locationService->isEnabled() && $locationService->hasLocation()) {
            $locationFilters = $locationService->getFilters();
            if (empty($filters['country_code']) && !empty($locationFilters['country_code'])) {
                $filter[] = ['term' => ['country_code' => $locationFilters['country_code']]];
            }
            if (empty($filters['region']) && !empty($locationFilters['region'])) {
                $filter[] = ['term' => ['region' => $locationFilters['region']]];
            }
        }

        if (!empty($filters['country_code'])) {
            $filter = array_values(array_filter($filter, fn ($item) => !isset($item['term']['country_code'])));
            $filter[] = ['term' => ['country_code' => $filters['country_code']]];
        }
        if (!empty($filters['region'])) {
            $filter = array_values(array_filter($filter, function ($item) {
                return !isset($item['wildcard']['region']) && !isset($item['term']['region']);
            }));
            $filter[] = ['wildcard' => ['region' => '*' . $filters['region'] . '*']];
        }

        if (!empty($filters['user_id'])) {
            if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== 'all') {
                $filter[] = ['term' => ['status' => $filters['status']]];
            }
        } else {
            $status = $filters['status'] ?? 'active';
            $filter[] = ['term' => ['status' => $status]];
        }

        if (isset($filters['min_price'])) {
            $filter[] = ['range' => ['price' => ['gte' => (float) $filters['min_price']]]];
        }
        if (isset($filters['max_price'])) {
            $filter[] = ['range' => ['price' => ['lte' => (float) $filters['max_price']]]];
        }
        if (isset($filters['has_images']) && $filters['has_images']) {
            $filter[] = ['term' => ['has_images' => true]];
        }
        if (isset($filters['is_free'])) {
            $filter[] = ['term' => ['is_free' => filter_var($filters['is_free'], FILTER_VALIDATE_BOOLEAN)]];
        }
        if (isset($filters['is_price_negotiable'])) {
            $filter[] = ['term' => ['is_price_negotiable' => filter_var($filters['is_price_negotiable'], FILTER_VALIDATE_BOOLEAN)]];
        }

        $query = $this->buildQuery($must, $filter, $should, $mustNot);
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $query['from'] = ($page - 1) * $perPage;
        $query['size'] = $perPage;

        $sortBy = $filters['sort'] ?? 'newest_ads';
        switch ($sortBy) {
            case 'most_viewed':
            case 'views_desc':
                $query['sort'] = [['views_count' => ['order' => 'desc']]];
                break;
            case 'least_viewed':
                $query['sort'] = [['views_count' => ['order' => 'asc']]];
                break;
            case 'newest_ads':
                $query['sort'] = [
                    ['published_at' => ['order' => 'desc', 'missing' => '_last']],
                    ['created_at' => ['order' => 'desc']],
                ];
                break;
            case 'lowest_price':
            case 'price_asc':
                $query['sort'] = [['price' => ['order' => 'asc', 'missing' => '_last']]];
                break;
            case 'highest_price':
            case 'price_desc':
                $query['sort'] = [['price' => ['order' => 'desc', 'missing' => '_last']]];
                break;
            case 'oldest':
                $query['sort'] = [['published_at' => ['order' => 'asc', 'missing' => '_last']]];
                break;
            default:
                $query['sort'] = [
                    ['published_at' => ['order' => 'desc', 'missing' => '_last']],
                    ['created_at' => ['order' => 'desc']],
                ];
        }

        return $this->search($this->index, $query);
    }
}
