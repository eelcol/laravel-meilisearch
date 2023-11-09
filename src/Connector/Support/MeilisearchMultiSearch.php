<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchQueryCollection;
use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;

/**
 * When using this class, all results of the separate queries are returned in 1 collection
 * That means that this works best when all queries query the same index
 */
class MeilisearchMultiSearch
{
    /**
     * @var array<MeilisearchQuery>
     */
    protected array $queries = [];

    public function addQuery(MeilisearchQuery $query): self
    {
        $this->queries[] = $query;

        return $this;
    }

    public function getQueryParam(): array
    {
        $queries = [];
        foreach ($this->queries as $query) {
            $queries[] = ['indexUid' => $query->getIndex()] + $query->getMeilisearchDataForMainQuery();
        }

        return $queries;
    }

    public function perform(): MeilisearchQueryCollection
    {
        return Meilisearch::multipleSearchDocuments($this);
    }
}