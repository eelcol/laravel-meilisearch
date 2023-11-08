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
use Eelcol\LaravelMeilisearch\Exceptions\CannotFilterOnAttribute;
use Eelcol\LaravelMeilisearch\Exceptions\CannotSortByAttribute;
use Eelcol\LaravelMeilisearch\Exceptions\IncorrectMeilisearchKey;
use Eelcol\LaravelMeilisearch\Exceptions\IndexNotFound;
use Eelcol\LaravelMeilisearch\Exceptions\NoMeilisearchHostGiven;
use Eelcol\LaravelMeilisearch\Exceptions\NotEnoughDocumentsToOrderRandomly;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class MeilisearchConnector
{
    protected PendingRequest $http;

    /**
     * @throws NoMeilisearchHostGiven
     */
    public function __construct(array $connection_data)
    {
        if (!isset($connection_data['host'])) {
            throw new NoMeilisearchHostGiven();
        }

        $this->http = Http::baseUrl($connection_data['host']);

        if (isset($connection_data['key']) && !empty($connection_data['key'])) {
            $this->http->withToken($connection_data['key']);
        }
    }

    /**
     * @param string $path
     * @param array<string, mixed> $query
     * @return MeilisearchResponse
     * @throws IncorrectMeilisearchKey
     */
    protected function request(string $path, array $query = []): MeilisearchResponse
    {
        $response = $this->http->get($path, $query);

        if ($response->status() == 403) {
            throw new IncorrectMeilisearchKey("Incorrect Meilisearch key is given. Enter a blank string to not use a key.");
        }

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
        return new MeilisearchIndexCollection($this->request("indexes"));
    }

    public function indexExists(string $index): bool
    {
        $indexes = $this->getAllIndexes();

        return $indexes->where('uid', $index)->count() > 0;
    }

    /**
     * @throws IndexNotFound
     */
    public function getIndexInformation(string $index): MeilisearchIndexItem
    {
        $item = $this->getAllIndexes()->firstWhere('uid', $index);
        if (!$item) {
            throw new IndexNotFound($index);
        }

        return $item;
    }

    public function createIndex(string $index, string $primaryKey = 'id'): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->postRequest("indexes", [
                'uid' => $index,
                'primaryKey' => $primaryKey
            ])
        );
    }

    public function deleteIndex(string $index): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->deleteRequest("indexes/" . $index)
        );
    }

    public function swapIndex(string $indexA, string $indexB): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->postRequest("swap-indexes", [
                [
                    "indexes" => [$indexA, $indexB]
                ]
            ])
        );
    }

    /**
     * @param string $index
     * @param string $new_index_name
     * @return bool
     * @throws \Eelcol\LaravelMeilisearch\Exceptions\IndexAlreadyExists
     * @throws \Eelcol\LaravelMeilisearch\Exceptions\IndexNotFound
     * @throws \Eelcol\LaravelMeilisearch\Exceptions\MissingDocumentId
     *
     * copy the settings of an index to a new index
     */
    public function copyIndex(string $index, string $new_index_name): bool
    {
        $index_info = $this->getIndexInformation($index);
        $task = $this->createIndex($new_index_name, $index_info->getPrimaryKey());

        // now wait till the task is finished
        $task->checkStatus();
        while ($task->isNotSucceeded()) {
            if ($task->isFailed()) {
                trigger_error("Failed creating index!");
                return false;
            }

            // wait 1 second
            sleep(1);
            $task->checkStatus();
        }

        // set filterable, searchable and sortable attributes
        $this->updateFilterableAttributes($new_index_name, $this->getFilterableAttributes($index)->getData());
        $this->updateSearchableAttributes($new_index_name, $this->getSearchableAttributes($index)->getData());
        $this->updateSortableAttributes($new_index_name, $this->getSortableAttributes($index)->getData());

        // set the maximum number of total hits
        $max_total_hits = $this->getPaginationSettings($index)->getData()['maxTotalHits'];
        $this->setMaxTotalHits($new_index_name, $max_total_hits);

        // set the maximum facet values
        $max_facet_values = $this->getFacetingSettings($index)->getData()['maxValuesPerFacet'];
        $this->setMaxValuesPerFacet($new_index_name, $max_facet_values);

        return true;
    }

    /**
     * @param string $index
     * @param array|object $data
     */
    public function addDocument(string $index, mixed $data): MeilisearchTask
    {
        $data = $this->transformData($data);

        return new MeilisearchTask(
            $this->postRequest("indexes/".$index."/documents", [$data])
        );
    }

    /**
     * @param string $index
     * @param array|object $data
     */
    public function addDocuments(string $index, mixed $data): MeilisearchTask
    {
        if (is_a($data, Collection::class)) {
            $data = $data->filter()->map(function ($item) {
                return $this->transformData($item);
            })->toArray();
        }

        return new MeilisearchTask(
            $this->postRequest("indexes/".$index."/documents", $data)
        );
    }

    /**
     * @param string $index
     * @param array<string, mixed> $query
     * @return MeilisearchDocumentsCollection
     */
    public function getDocuments(string $index, array $query = []): MeilisearchDocumentsCollection
    {
        return new MeilisearchDocumentsCollection(
            $index,
            $this->request("indexes/".$index."/documents", $query)
        );
    }

    public function getDocument(string $index, int $id): MeilisearchDocument
    {
        return new MeilisearchDocument(
            $this->request("indexes/".$index."/documents/".$id)
        );
    }

    /**
     * @param array<int> $ids
     */
    public function deleteDocuments(string $index, array $ids): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->postRequest("indexes/".$index."/documents/delete-batch", $ids)
        );
    }

    public function deleteDocument(string $index, int $id): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->deleteRequest("indexes/".$index."/documents/".$id)
        );
    }

    public function query(string $index): MeilisearchQuery
    {
        return new MeilisearchQuery($index);
    }

    /**
     * @param MeilisearchQuery $query
     * @return MeilisearchQueryCollection
     * @throws NotEnoughDocumentsToOrderRandomly
     * @throws CannotFilterOnAttribute
     * @throws CannotSortByAttribute
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
            $meta_result = $this->http->post("indexes/".$query->getIndex()."/search", $query->getMeilisearchDataForMetadataQuery());
        }

        if ($query->shouldOrderRandomly()) {
            return $this->searchDocumentsInRandomOrder($query);
        }

        $response = $this->http->post("indexes/".$query->getIndex()."/search", $query->getMeilisearchDataForMainQuery());

        if ($response->clientError()) {
            $message = $response->json('message');

            if (preg_match("/Attribute `(.*)` is not filterable/", $message, $matches)) {
                throw new CannotFilterOnAttribute($matches[1]);
            }

            if (preg_match("/Attribute `(.*)` is not sortable/", $message, $matches)) {
                throw new CannotSortByAttribute($matches[1]);
            }
        }

        return (new MeilisearchQueryCollection($response))->setMetaData($meta_result);
    }

    /**
     * @throws NotEnoughDocumentsToOrderRandomly
     * @throws CannotFilterOnAttribute
     * @throws CannotSortByAttribute
     */
    protected function searchDocumentsInRandomOrder(MeilisearchQuery $query): MeilisearchQueryCollection
    {
        // first load the total number of documents in this index
        // applying the requested filters
        $response = $this->http->post("indexes/".$query->getIndex()."/search", [
            'q' => $query->getSearchQuery()
        ] + [
            'filter' => $query->getSearchFilters(),
            'page' => 1,
            'hitsPerPage' => 1,
        ]);

        if ($response->clientError()) {
            $message = $response->json('message');

            if (preg_match("/Attribute `(.*)` is not filterable/", $message, $matches)) {
                throw new CannotFilterOnAttribute($matches[1]);
            }

            if (preg_match("/Attribute `(.*)` is not sortable/", $message, $matches)) {
                throw new CannotSortByAttribute($matches[1]);
            }
        }

        $response = new MeilisearchResponse($response);
        $num_documents = $response->getTotalHits();

        if ($num_documents < $query->getSearchLimit()) {
            throw new NotEnoughDocumentsToOrderRandomly("Only " . $num_documents . " found.");
        }

        // now get random items
        $numbers = range(0, ($num_documents-1));
        shuffle($numbers);
        $items = new MeilisearchQueryCollection();

        for ($i = 0; $i < $query->getSearchLimit(); $i++) {
            $offset = $numbers[$i];

            $newQuery = clone $query;
            $newQuery->page($offset)->hitsPerPage(1);

            $response = $this->searchDocuments($newQuery);
            $items->pushCollection($response);
        }

        return $items;
    }

    public function updateFilterableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->putRequest("indexes/".$index."/settings/filterable-attributes", $attributes)
        );
    }

    public function getFilterableAttributes(string $index): MeilisearchResponse
    {
        return $this->request("indexes/".$index."/settings/filterable-attributes");
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
            $this->putRequest("indexes/".$index."/settings/searchable-attributes", $attributes)
        );
    }

    public function getSearchableAttributes(string $index): MeilisearchResponse
    {
        return $this->request("indexes/".$index."/settings/searchable-attributes");
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
        return $this->request("indexes/".$index."/settings/sortable-attributes");
    }

    public function updateSortableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->putRequest("indexes/".$index."/settings/sortable-attributes", $attributes)
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

    public function setMaxTotalHits(string $index, int $max_total_hits): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->patchRequest("indexes/".$index."/settings/pagination", [
                'maxTotalHits' => $max_total_hits
            ])
        );
    }

    public function getPaginationSettings($index): MeilisearchResponse
    {
        return $this->request("indexes/".$index."/settings/pagination");
    }

    public function setMaxValuesPerFacet(string $index, int $max_values_per_facet): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->patchRequest("indexes/".$index."/settings/faceting", [
                'maxValuesPerFacet' => $max_values_per_facet
            ])
        );
    }

    public function getFacetingSettings($index): MeilisearchResponse
    {
        return $this->request("indexes/".$index."/settings/faceting");
    }

    public function getHealth(): MeilisearchHealth
    {
        return new MeilisearchHealth(
            $this->request("health")
        );
    }

    public function getTasks(): MeilisearchTasksCollection
    {
        return new MeilisearchTasksCollection(
            $this->request("tasks")
        );
    }

    public function getTask(int $taskId): MeilisearchTask
    {
        return new MeilisearchTask($this->request("tasks/" . $taskId));
    }

    public function getVersion(): string
    {
        return $this->request("version")['pkgVersion'];
    }

    public function getStats(?string $index = null): MeilisearchResponse
    {
        if ($index) {
            return $this->request("indexes/".$index."/stats");
        }

        return $this->request("stats");
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