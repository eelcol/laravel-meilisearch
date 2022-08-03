<?php

namespace Eelcol\LaravelMeilisearch\Connector;

use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchDocumentsCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchIndexCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchQueryCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchTasksCollection;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchHealth;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchIndexItem;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchTask;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Exceptions\NotEnoughDocumentsToOrderRandomly;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MeilisearchConnector
{
    protected PendingRequest $http;

    public function __construct(array $connection_data)
    {
        $this->http = Http::baseUrl($connection_data['host'])->withToken($connection_data['key']);
    }

    /**
     * @param  string  $path
     * @param  array<string, mixed>  $query
     * @return MeilisearchResponse
     */
    protected function request(string $path, array $query = []): MeilisearchResponse
    {
        $response = $this->http->get($path, $query);

        return new MeilisearchResponse($response);
    }

    protected function postRequest(string $path, array $options = []): MeilisearchResponse
    {
        $response = $this->http->post($path, $options);

        return new MeilisearchResponse($response);
    }

    protected function putRequest(string $path, array $options = []): MeilisearchResponse
    {
        $response = $this->http->put($path, $options);

        return new MeilisearchResponse($response);
    }

    protected function patchRequest(string $path, array $options = []): MeilisearchResponse
    {
        $response = $this->http->patch($path, $options);

        return new MeilisearchResponse($response);
    }

    protected function deleteRequest(string $path): MeilisearchResponse
    {
        $response = $this->http->delete($path);

        return new MeilisearchResponse($response);
    }

    public function getAllIndexes(): MeilisearchIndexCollection
    {
        return new MeilisearchIndexCollection($this->request('indexes'));
    }

    public function indexExists(string $index): bool
    {
        $indexes = $this->getAllIndexes();

        return $indexes->where('uid', $index)->count() > 0;
    }

    public function getIndexInformation(string $index): MeilisearchIndexItem
    {
        return $this->getAllIndexes()->firstWhere('uid', $index);
    }

    public function createIndex(string $index, string $primaryKey = 'id'): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->postRequest('indexes', [
                'uid' => $index,
                'primaryKey' => $primaryKey,
            ])
        );
    }

    public function deleteIndex(string $index): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->deleteRequest('indexes/'.$index)
        );
    }

    /**
     * @param  string  $index
     * @param  array|object  $data
     */
    public function addDocument(string $index, mixed $data): MeilisearchTask
    {
        $data = $this->transformData($data);

        return new MeilisearchTask(
            $this->postRequest('indexes/'.$index.'/documents', [$data])
        );
    }

    /**
     * @param  string  $index
     * @param  array|object  $data
     */
    public function addDocuments(string $index, mixed $data): MeilisearchTask
    {
        if (is_a($data, Collection::class)) {
            $data = $data->filter()->map(function ($item) {
                return $this->transformData($item);
            })->toArray();
        }

        return new MeilisearchTask(
            $this->postRequest('indexes/'.$index.'/documents', $data)
        );
    }

    /**
     * @param  string  $index
     * @param  array<string, mixed>  $query
     * @return MeilisearchDocumentsCollection
     */
    public function getDocuments(string $index, array $query = []): MeilisearchDocumentsCollection
    {
        return new MeilisearchDocumentsCollection(
            $index,
            $this->request('indexes/'.$index.'/documents', $query)
        );
    }

    public function getDocument(string $index, int $id): MeilisearchDocument
    {
        return new MeilisearchDocument(
            $this->request('indexes/'.$index.'/documents/'.$id)
        );
    }

    public function query(string $index): MeilisearchQuery
    {
        return new MeilisearchQuery($index);
    }

    /**
     * @param  MeilisearchQuery  $query
     * @return MeilisearchQueryCollection
     *
     * @throws NotEnoughDocumentsToOrderRandomly
     */
    public function searchDocuments(MeilisearchQuery $query): MeilisearchQueryCollection
    {
        $meta_result = null;
        if ($query->shouldQueryForMetadata()) {
            // perform a separate query with other filters
            // for the meta data
            // for example: you want to query all products with color = black
            // but you still want to receive the number of products for the other colors
            // so you still want to know how many products have the color 'red' or 'yellow' etc
            // in that case, we need to make an extra query for this meta-data
            // and skip some filters
            $meta_result = $this->http->post('indexes/'.$query->getIndex().'/search', [
                'q' => $query->getSearchQuery(),
            ] + [
                'filter' => $query->getSearchFiltersForMetadata(),
                'limit' => $query->getSearchLimit(),
                'offset' => $query->getSearchOffset(),
                'facets' => $query->getFacetsDistribution(),
                'sort' => $query->getSearchOrdering(),
            ]);
        }

        if ($query->shouldOrderRandomly()) {
            return $this->searchDocumentsInRandomOrder($query);
        }

        $response = $this->http->post('indexes/'.$query->getIndex().'/search', [
            'q' => $query->getSearchQuery(),
        ] + [
            'filter' => $query->getSearchFilters(),
            'limit' => $query->getSearchLimit(),
            'offset' => $query->getSearchOffset(),
            'facets' => $query->getFacetsDistribution(),
            'sort' => $query->getSearchOrdering(),
        ]);

        return (new MeilisearchQueryCollection($response))->setMetaData($meta_result);
    }

    /**
     * @throws NotEnoughDocumentsToOrderRandomly
     */
    protected function searchDocumentsInRandomOrder(MeilisearchQuery $query): MeilisearchQueryCollection
    {
        // first load the total number of documents in this index
        // applying the requested filters
        $response = $this->postRequest('indexes/'.$query->getIndex().'/search', [
            'q' => $query->getSearchQuery(),
        ] + [
            'filter' => $query->getSearchFilters(),
        ]);

        $num_documents = $response['estimatedTotalHits'];

        if ($num_documents < $query->getSearchLimit()) {
            throw new NotEnoughDocumentsToOrderRandomly('Only '.$num_documents.' found.');
        }

        // now get random items
        $numbers = range(0, ($num_documents - 1));
        shuffle($numbers);
        $items = new MeilisearchQueryCollection();

        for ($i = 0; $i < $query->getSearchLimit(); $i++) {
            $offset = $numbers[$i];

            $newQuery = clone $query;
            $newQuery->limit(1)->offset($offset);

            $response = $this->searchDocuments($newQuery);
            $items->pushCollection($response);
        }

        return $items;
    }

    public function updateFilterableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->putRequest('indexes/'.$index.'/settings/filterable-attributes', $attributes)
        );
    }

    public function getFilterableAttributes(string $index): MeilisearchResponse
    {
        return $this->request('indexes/'.$index.'/settings/filterable-attributes');
    }

    public function syncFilterableAttributes(string $index, array $attributes): ?MeilisearchTask
    {
        $current_attributes = $this->getFilterableAttributes($index)->getData();

        array_multisort($attributes);
        array_multisort($current_attributes);

        if (serialize($attributes) != serialize($current_attributes)) {
            return $this->updateFilterableAttributes($index, $attributes);
        }

        return null;
    }

    public function updateSearchableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->putRequest('indexes/'.$index.'/settings/searchable-attributes', $attributes)
        );
    }

    public function getSearchableAttributes(string $index): MeilisearchResponse
    {
        return $this->request('indexes/'.$index.'/settings/searchable-attributes');
    }

    public function syncSearchableAttributes(string $index, array $attributes): ?MeilisearchTask
    {
        $current_attributes = $this->getSearchableAttributes($index)->getData();

        array_multisort($attributes);
        array_multisort($current_attributes);

        if (serialize($attributes) != serialize($current_attributes)) {
            return $this->updateSearchableAttributes($index, $attributes);
        }

        return null;
    }

    public function getSortableAttributes(string $index): MeilisearchResponse
    {
        return $this->request('indexes/'.$index.'/settings/sortable-attributes');
    }

    public function updateSortableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->putRequest('indexes/'.$index.'/settings/sortable-attributes', $attributes)
        );
    }

    public function syncSortableAttributes(string $index, array $attributes): ?MeilisearchTask
    {
        $current_attributes = $this->getSortableAttributes($index)->getData();

        array_multisort($attributes);
        array_multisort($current_attributes);

        if (serialize($attributes) != serialize($current_attributes)) {
            return $this->updateSortableAttributes($index, $attributes);
        }

        return null;
    }

    public function getRankingRules(string $index): MeilisearchResponse
    {
        return $this->request('indexes/'.$index.'/settings/ranking-rules');
    }

    public function updateRankingRules(string $index, array $rules): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->putRequest('indexes/'.$index.'/settings/ranking-rules', $rules)
        );
    }

    public function syncRankingRules(string $index, array $rules)
    {
        $current_rules = $this->getRankingRules($index)->getData();

        array_multisort($rules);
        array_multisort($current_rules);

        if (serialize($rules) != serialize($current_rules)) {
            return $this->updateRankingRules($index, $rules);
        }

        return null;
    }

    public function setMaxTotalHits(string $index, int $max_total_hits): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->patchRequest('indexes/'.$index.'/settings/pagination', [
                'maxTotalHits' => $max_total_hits,
            ])
        );
    }

    public function setMaxValuesPerFacet(string $index, int $max_values_per_facet): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->patchRequest('indexes/'.$index.'/settings/faceting', [
                'maxValuesPerFacet' => $max_values_per_facet,
            ])
        );
    }

    public function getHealth(): MeilisearchHealth
    {
        return new MeilisearchHealth(
            $this->request('health')
        );
    }

    public function getTasks(): MeilisearchTasksCollection
    {
        return new MeilisearchTasksCollection(
            $this->request('tasks')
        );
    }

    public function getTask(int $taskId): MeilisearchTask
    {
        return new MeilisearchTask($this->request('tasks/'.$taskId));
    }

    public function getVersion(): string
    {
        return $this->request('version')['pkgVersion'];
    }

    public function getStats(?string $index = null): MeilisearchResponse
    {
        if ($index) {
            return $this->request('indexes/'.$index.'/stats');
        }

        return $this->request('stats');
    }

    protected function transformData(mixed $data): array
    {
        if (is_object($data) && method_exists($data, 'toMeilisearch')) {
            $data = $data->toMeilisearch();
        } elseif (is_object($data) && method_exists($data, 'toSearchableArray')) {
            $data = $data->toSearchableArray();
        } elseif (is_object($data)) {
            $data = $data->toArray();
        }

        $data += ['updated_at' => now()->format('Y-m-d H:i:s')];

        return $data;
    }
}
