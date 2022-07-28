<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchTask;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchTask>
 */
class MeilisearchTasksCollection extends MeilisearchCollection
{
    public function __construct(MeilisearchResponse $results)
    {
        $data = [];
        foreach ($results as $item) {
            $data[] = MeilisearchTask::fromArray($item);
        }

        $this->data = collect($data);
        return $this->data;
    }
}