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

    protected array $facetDistribution = [];

    public function __construct(?Response $data = null)
    {
        $hits = [];
        if ($data) {
            $this->result = $data;

            $results = $data->json('results');
            if ($results) {
                // query result with multiple results
                // happens after query-ing `multi-search` endpoint
                $hits = $results[0]['hits'];

                if (isset($results[0]['facetDistribution'])) {
                    $this->facetDistribution = $results[0]['facetDistribution'];
                }

                // now combine the facet distribution of the other metadata queries
                for ($i = 1; $i < count($results); $i++) {
                    $this->facetDistribution = array_merge($this->facetDistribution, $results[$i]['facetDistribution']);
                }
            } else {
                $hits = $data->json('hits');
                $this->facetDistribution = $data->json('facetDistribution') ?? [];
            }
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

    public function getFacetsDistribution(): array
    {
        return $this->facetDistribution;
    }
}