<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;

class MeilisearchDocument extends MeilisearchModel
{
    public function __construct(MeilisearchResponse $response)
    {
        $this->data = $response->getData();
    }
}