<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use Illuminate\Http\Client\Response;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchDocument>
 */
class MeilisearchQueryCollection extends MeilisearchCollection
{
    protected Response $result;

    protected Response $metadata;

    public function __construct(?Response $data = null)
    {
        $hits = [];
        if ($data) {
            $this->result = $data;
            $hits = $data->json('hits');
        }

        $this->data = collect(array_map(function ($item) {
            return MeilisearchDocument::fromArray($item);
        }, $hits));
    }

    public function castAs(string $class): self
    {
        $this->data->transform(function ($item) use ($class) {
            return new $class($item->getData());
        });

        return $this;
    }

    public function setMetadata(?Response $searchResult = null)
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

        return $this->result->json('totalHits');
    }

    public function hasNextPage(): bool
    {
        if (!$this->result->json('page') || !$this->result->json('totalPages')) {
            return false;
        }

        return $this->result->json('page') < $this->result->json('totalPages');
    }

    public function getFacetsDistribution(): ?array
    {
        if (isset($this->metadata)) {
            return $this->metadata->json('facetDistribution');
        }

        if (isset($this->result)) {
            return $this->result->json('facetDistribution');
        }

        return null;
    }
}