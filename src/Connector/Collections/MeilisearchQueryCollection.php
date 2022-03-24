<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;
use MeiliSearch\Search\SearchResult;

/**
 * @implements IteratorAggregate<MeilisearchDocument>
 */
class MeilisearchQueryCollection extends MeilisearchCollection
{
    protected SearchResult $result;

    protected SearchResult $metadata;

    public function __construct(?SearchResult $data = null)
    {
        $hits = [];
        if ($data) {
            $hits = $data->getHits();
            $this->result = $data;
        }

        $this->data = collect(array_map(function ($item) {
            return new MeilisearchDocument($item);
        }, $hits));
    }

    public function setMetadata(?SearchResult $searchResult = null)
    {
        if (!is_null($searchResult)) {
            $this->metadata = $searchResult;
        }

        return $this;
    }

    public function pushCollection(MeilisearchQueryCollection $collection): self
    {
        foreach ($collection as $item) {
            $this->data->push($item);
        }

        return $this;
    }

    public function totalCount(): ?int
    {
        if (!isset($this->result)) {
            return null;
        }

        return $this->result->getNbHits();
    }

    public function getFacetsDistribution(): ?array
    {
        if (isset($this->metadata)) {
            return $this->metadata->getFacetsDistribution();
        }

        if (isset($this->result)) {
            return $this->result->getFacetsDistribution();
        }

        return null;
    }
}