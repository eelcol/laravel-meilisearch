<?php

namespace Eelcol\LaravelMeilisearch\Connector;

use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchDocumentsCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchIndexCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchQueryCollection;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchHealth;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchIndexItem;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchTask;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Exceptions\NotEnoughDocumentsToOrderRandomly;
use Illuminate\Support\Collection;
use MeiliSearch\Client;

class MeilisearchConnector
{
    protected Client $client;

    public function __construct(array $connection_data)
    {
        $this->client = new Client($connection_data['host'], $connection_data['key']);
    }

    public function getAllIndexes(): MeilisearchIndexCollection
    {
        return new MeilisearchIndexCollection($this->client->getAllIndexes());
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
            $this->client->createIndex($index, ['primaryKey' => $primaryKey])
        );
    }

    public function deleteIndex(string $index): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->client->deleteIndex($index)
        );
    }

    /**
     * @param string $index
     * @param array|object $data
     */
    public function addDocument(string $index, mixed $data): MeilisearchTask
    {
        $data = $this->transformData($data);

        return new MeilisearchTask(
            $this->client->index($index)->addDocuments([$data])
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
            $this->client->index($index)->addDocuments($data)
        );
    }

    public function getDocuments(string $index, array $query = []): MeilisearchDocumentsCollection
    {
        return new MeilisearchDocumentsCollection(
            $this->client->index($index)->getDocuments($query)
        );
    }

    public function getDocument(string $index, int $id): MeilisearchDocument
    {
        return new MeilisearchDocument(
            $this->client->index($index)->getDocument($id)
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
            $meta_result = $this->client->index($query->getIndex())->search($query->getSearchQuery(), [
                'filter' => $query->getSearchFiltersForMetadata(),
                'limit' => $query->getSearchLimit(),
                'offset' => $query->getSearchOffset(),
                'facetsDistribution' => $query->getFacetsDistribution(),
                'sort' => $query->getSearchOrdering()
            ]);
        }

        if ($query->shouldOrderRandomly()) {
            return $this->searchDocumentsInRandomOrder($query);
        }

        $response = $this->client->index($query->getIndex())->search($query->getSearchQuery(), [
            'filter' => $query->getSearchFilters(),
            'limit' => $query->getSearchLimit(),
            'offset' => $query->getSearchOffset(),
            'facetsDistribution' => $query->getFacetsDistribution(),
            'sort' => $query->getSearchOrdering()
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
        $response = $this->client->index($query->getIndex())->search($query->getSearchQuery(), [
            'filter' => $query->getSearchFilters()
        ]);

        $rawResponse = $response->getRaw();
        $num_documents = $rawResponse['nbHits'];

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
            $newQuery->limit(1)->offset($offset);

            $response = $this->searchDocuments($newQuery);
            $items->pushCollection($response);
        }

        return $items;
    }

    public function updateFilterableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->client->index($index)->updateFilterableAttributes($attributes)
        );
    }

    public function getFilterableAttributes(string $index): array
    {
        return $this->client->index($index)->getFilterableAttributes();
    }

    public function syncFilterableAttributes(string $index, array $attributes): ?MeilisearchTask
    {
        $current_attributes = $this->getFilterableAttributes($index);

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
            $this->client->index($index)->updateSearchableAttributes($attributes)
        );
    }

    public function getSearchableAttributes(string $index): array
    {
        return $this->client->index($index)->getSearchableAttributes();
    }

    public function syncSearchableAttributes(string $index, array $attributes): ?MeilisearchTask
    {
        $current_attributes = $this->getSearchableAttributes($index);

        array_multisort($attributes);
        array_multisort($current_attributes);

        if (serialize($attributes) != serialize($current_attributes)) {
            return $this->updateSearchableAttributes($index, $attributes);
        }

        return null;
    }

    public function getSortableAttributes(string $index): array
    {
        return $this->client->index($index)->getSortableAttributes();
    }

    public function updateSortableAttributes(string $index, array $attributes): MeilisearchTask
    {
        return new MeilisearchTask(
            $this->client->index($index)->updateSortableAttributes($attributes)
        );
    }

    public function syncSortableAttributes(string $index, array $attributes): ?MeilisearchTask
    {
        $current_attributes = $this->getSortableAttributes($index);

        array_multisort($attributes);
        array_multisort($current_attributes);

        if (serialize($attributes) != serialize($current_attributes)) {
            return $this->updateSortableAttributes($index, $attributes);
        }

        return null;
    }

    public function getHealth(): MeilisearchHealth
    {
        return new MeilisearchHealth($this->client->health());
    }

    public function getTasks()
    {
        return $this->client->getTasks();
    }

    public function getTask(int $taskId): MeilisearchTask
    {
        return new MeilisearchTask($this->client->getTask($taskId));
    }

    public function getVersion(): string
    {
        return $this->client->version()['pkgVersion'];
    }

    public function getStats(?string $index = null)
    {
        if ($index) {
            return $this->client->index($index)->stats();
        }

        return $this->client->stats();
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