<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchFacetValue;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchDocument>
 */
class MeilisearchFacetValuesCollection extends MeilisearchCollection
{
    public function __construct(MeilisearchResponse $results)
    {
        $data = [];
        foreach ($results as $item) {
            $data[] = new MeilisearchFacetValue($item);
        }

        $this->data = collect($data);
    }
}