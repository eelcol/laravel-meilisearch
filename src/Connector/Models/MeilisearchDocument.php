<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;

class MeilisearchDocument extends MeilisearchModel
{
    public function __construct(array $data)
    {
        $this->data = $data;
    }
}