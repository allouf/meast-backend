<?php

namespace App\Library\Elasticsearch;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Exception;
use Elastic\Elasticsearch\Exception\ClientResponseException;


class Elasticsearch
{
    protected Client $client;

    public function __construct()
    {
        $host = config('elasticsearch.host');
        $username = config('elasticsearch.username');
        $password = config('elasticsearch.password');

        $this->client = ClientBuilder::create()
            ->setHosts([$host])
            ->setBasicAuthentication($username, $password)
            ->build();
    }


    /**
     * Check connection with cluster
     */
    public function ping()
    {
        try {
            return $this->client->ping();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create index with mapping
     */
    public function createIndex(string $index, array $mapping = [])
    {
        return $this->client->indices()->create([
            'index' => $index,
            'body' => $mapping
        ]);
    }

    /**
     * Delete index
     */
    public function deleteIndex(string $index)
    {
        return $this->client->indices()->delete([
            'index' => $index
        ]);
    }

    /**
     * Update mapping for an existing index
     */
    public function updateMapping(string $index, array $mapping)
    {
        return $this->client->indices()->putMapping([
            'index' => $index,
            'body' => $mapping
        ]);
    }

    /**
     * Index (create/update) a document
     */
    public function indexDocument(string $index, string|int $id, array $data)
    {
        return $this->client->index([
            'index' => $index,
            'id'    => $id,
            'body'  => $data,
        ]);
    }

    /**
     * Get document by id
     */
    public function getDocument(string $index, string|int $id)
    {
        return $this->client->get([
            'index' => $index,
            'id'    => $id
        ]);
    }

    /**
     * Delete document
     */
    // public function deleteDocument(string $index, string|int $id)
    // {
    //     return $this->client->delete([
    //         'index' => $index,
    //         'id'    => $id
    //     ]);
    // }



    public function deleteDocument(string $index, string|int $id, bool $ignoreNotFound = false)
    {
        try {
            $params = ['index' => $index, 'id' => $id];
            if ($ignoreNotFound) {
                $params['ignore'] = [404];
            }
            return $this->client->delete($params);
        } catch (ClientResponseException $e) {
            if ($ignoreNotFound && $e->getResponse()?->getStatusCode() === 404) {
                return null; // تجاهل مستند غير موجود
            }
            throw $e;
        }
    }




    /**
     * Search in index
     */
    public function search(string $index, array $query)
    {
        return $this->client->search([
            'index' => $index,
            'body'  => $query
        ]);
    }

    /**
     * Helper query builder (bool query)
     */
    public function buildQuery(array $must = [], array $filter = [], array $should = [], array $mustNot = [])
    {
        return [
            'query' => [
                'bool' => [
                    'must'     => $must,
                    'filter'   => $filter,
                    'should'   => $should,
                    'must_not' => $mustNot,
                ]
            ]
        ];
    }
}
