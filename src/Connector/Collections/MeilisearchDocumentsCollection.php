<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchDocument>
 */
class MeilisearchDocumentsCollection extends MeilisearchCollection
{
    protected string $index;
    protected int $offset;
    protected int $limit;
    protected int $count;

    public function __construct(string $index, MeilisearchResponse $results)
    {
        $this->index = $index;
        $this->offset = $results->getOffset();
        $this->limit = $results->getLimit();
        $this->count = $results->getTotal();

        $data = [];
        foreach ($results as $item) {
            $data[] = MeilisearchDocument::fromArray($item);
        }

        $this->data = collect($data);
        return $this->data;
    }

    public function hasNextPage()
    {
        return ($this->offset + $this->limit) < $this->count;
    }

    public function getNextPage()
    {
        return Meilisearch::getDocuments($this->index, [
            'limit' => $this->limit,
            'offset' => ($this->offset + $this->limit)
        ]);
    }
}