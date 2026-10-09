<?php

namespace Modules\Job\Library;

use App\Library\Elasticsearch\Elasticsearch;
use Modules\Job\Models\Job;

class JobElasticsearch extends Elasticsearch
{
    protected string $index = 'job_postings';

    public function createJobIndex(): array
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'employer_id' => ['type' => 'integer'],
                    'category_id' => ['type' => 'integer'],
                    'title' => ['type' => 'text', 'analyzer' => 'standard', 'fields' => ['keyword' => ['type' => 'keyword', 'ignore_above' => 256]]],
                    'description' => ['type' => 'text', 'analyzer' => 'standard'],
                    'company_name' => [
                        'type' => 'text',
                        'analyzer' => 'standard',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256
                            ]
                        ]
                    ],
                    'employment_type' => ['type' => 'keyword'],
                    'experience_level' => ['type' => 'keyword'],
                    'country_code' => ['type' => 'keyword'],
                    'region' => ['type' => 'keyword'],
                    'work_mode' => ['type' => 'keyword'],
                    'salary_min' => ['type' => 'double'],
                    'salary_max' => ['type' => 'double'],
                    'application_deadline' => ['type' => 'date'],
                    'status' => ['type' => 'keyword'],
                    'created_at' => ['type' => 'date'],
                    'updated_at' => ['type' => 'date'],
                ]
            ]
        ];
        return $this->createIndex($this->index, $mapping)->asArray();
    }

    public function recreateJobIndex(): array
    {
        try {
            if ($this->client->indices()->exists(['index' => $this->index])->asBool()) {
                $this->deleteIndex($this->index);
            }
        } catch (\Exception $e) {
        }
        return $this->createJobIndex();
    }

    public function indexJob(Job $job): array
    {
        $data = [
            'employer_id' => (int) $job->employer_id,
            'category_id' => (int) $job->category_id,
            'title' => $job->title,
            'description' => $job->description ?? '',
            'company_name' => $job->employer?->company?->company_name ?? '',
            'employment_type' => $job->employment_type?->value ?? (string) $job->getRawOriginal('employment_type'),
            'experience_level' => $job->experience_level?->value ?? (string) $job->getRawOriginal('experience_level'),
            'country_code' => $job->country_code ?? '',
            'region' => $job->region ?? '',
            'work_mode' => $job->work_mode?->value ?? (string) $job->getRawOriginal('work_mode'),
            'salary_min' => $job->salary_min !== null ? (float) $job->salary_min : null,
            'salary_max' => $job->salary_max !== null ? (float) $job->salary_max : null,
            'application_deadline' => $job->application_deadline?->format('Y-m-d'),
            'status' => $job->status?->value ?? (string) $job->getRawOriginal('status'),
            'created_at' => $job->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'updated_at' => $job->updated_at?->toIso8601String() ?? now()->toIso8601String(),
        ];
        return $this->indexDocument($this->index, $job->id, $data)->asArray();
    }

    public function searchJobsWithFilters(array $filters): array
    {
        $must = [];
        $filter = [];
        $should = [];
        $mustNot = [];
        if (!empty($filters['search'])) {
            $must[] = [
                'multi_match' => [
                    'query' => $filters['search'],
                    'fields' => ['title^3', 'description^2'],
                    'type' => 'best_fields',
                    'operator' => 'and',
                    'fuzziness' => 'AUTO'
                ]
            ];
        }
        // Mirror JobFilter company_name (employer.company like '%company_name%') for Elastic
        if (!empty($filters['company_name'])) {
            $companyName = trim($filters['company_name']);
            $must[] = [
                'match' => [
                    'company_name' => [
                        'query' => $companyName,
                        'operator' => 'and',
                        'fuzziness' => 'AUTO',
                    ],
                ],
            ];
        }
        if (isset($filters['employer_id']))
            $filter[] = ['term' => ['employer_id' => (int) $filters['employer_id']]];
        if (!empty($filters['category_id']))
            $filter[] = ['term' => ['category_id' => (int) $filters['category_id']]];
        if (isset($filters['status']))
            $filter[] = ['term' => ['status' => $filters['status']]];
        if (!empty($filters['country_code']))
            $filter[] = ['term' => ['country_code' => $filters['country_code']]];
        if (!empty($filters['employment_type']))
            $filter[] = ['term' => ['employment_type' => $filters['employment_type']]];
        if (!empty($filters['experience_level']))
            $filter[] = ['term' => ['experience_level' => $filters['experience_level']]];
        if (!empty($filters['work_mode']))
            $filter[] = ['term' => ['work_mode' => $filters['work_mode']]];
        if (!empty($filters['application_deadline_from']))
            $filter[] = ['range' => ['application_deadline' => ['gte' => $filters['application_deadline_from']]]];
        if (!empty($filters['application_deadline_to']))
            $filter[] = ['range' => ['application_deadline' => ['lte' => $filters['application_deadline_to']]]];
        $minSalary = isset($filters['min_salary']) && $filters['min_salary'] !== '' && $filters['min_salary'] !== null
            ? (float) $filters['min_salary'] : null;
        $maxSalary = isset($filters['max_salary']) && $filters['max_salary'] !== '' && $filters['max_salary'] !== null
            ? (float) $filters['max_salary'] : null;
        if ($minSalary !== null && $maxSalary !== null && $minSalary > $maxSalary) {
            [$minSalary, $maxSalary] = [$maxSalary, $minSalary];
        }
        if ($minSalary !== null || $maxSalary !== null) {
            $salaryShould = [];
            if ($minSalary !== null && $maxSalary !== null) {
                $salaryShould[] = ['range' => ['salary_min' => ['gte' => $minSalary, 'lte' => $maxSalary]]];
                $salaryShould[] = ['range' => ['salary_max' => ['gte' => $minSalary, 'lte' => $maxSalary]]];
            } elseif ($minSalary !== null) {
                $salaryShould[] = ['range' => ['salary_min' => ['gte' => $minSalary]]];
                $salaryShould[] = ['range' => ['salary_max' => ['gte' => $minSalary]]];
            } else {
                $salaryShould[] = ['range' => ['salary_min' => ['lte' => $maxSalary]]];
                $salaryShould[] = ['range' => ['salary_max' => ['lte' => $maxSalary]]];
            }

            // Always allow jobs with no salary info at all
            $salaryShould[] = [
                'bool' => [
                    'must_not' => [
                        ['exists' => ['field' => 'salary_min']],
                        ['exists' => ['field' => 'salary_max']],
                    ]
                ]
            ];

            $filter[] = [
                'bool' => [
                    'should' => $salaryShould,
                    'minimum_should_match' => 1
                ]
            ];
        }
        $query = $this->buildQuery($must, $filter, $should, $mustNot);
        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 15);
        $query['from'] = ($page - 1) * $perPage;
        $query['size'] = $perPage;
        $query['sort'] = $this->buildSort($filters['sort'] ?? 'newest');
        return $this->search($this->index, $query)->asArray();
    }

    protected function buildSort(string $sort): array
    {
        $sortMap = [
            'newest' => [['created_at' => ['order' => 'desc']]],
            'oldest' => [['created_at' => ['order' => 'asc']]],
            'deadline_soon' => [['application_deadline' => ['order' => 'asc', 'missing' => '_last']], ['created_at' => ['order' => 'desc']]],
            'deadline_latest' => [['application_deadline' => ['order' => 'desc', 'missing' => '_last']], ['created_at' => ['order' => 'desc']]],
            'salary_high' => [['salary_max' => ['order' => 'desc', 'missing' => '_last']], ['created_at' => ['order' => 'desc']]],
            'salary_low' => [['salary_min' => ['order' => 'asc', 'missing' => '_last']], ['created_at' => ['order' => 'desc']]],
        ];
        return $sortMap[$sort] ?? $sortMap['newest'];
    }
}
