<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchDocument>
 */
class MeilisearchDocumentsCollection extends MeilisearchCollection
{
    public function __construct(array $data)
    {
        $this->data = collect(array_map(function ($item) {
            return new MeilisearchDocument($item);
        }, $data));
    }
}