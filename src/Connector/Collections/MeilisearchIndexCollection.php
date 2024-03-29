<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchIndexItem;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchIndexItem>
 */
class MeilisearchIndexCollection extends MeilisearchCollection
{
    public function __construct(MeilisearchResponse $results)
    {
        $data = [];
        foreach ($results as $item) {
            $data[] = new MeilisearchIndexItem($item);
        }

        $this->data = collect($data);
        return $this->data;
    }
}