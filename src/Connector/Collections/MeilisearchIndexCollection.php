<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchIndexItem;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchIndexItem>
 */
class MeilisearchIndexCollection extends MeilisearchCollection
{
    public function __construct(array $data)
    {
        $this->data = collect(array_map(function ($item) {
            return new MeilisearchIndexItem($item);
        }, $data));
    }
}