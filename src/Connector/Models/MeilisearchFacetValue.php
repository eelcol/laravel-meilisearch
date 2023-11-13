<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;

class MeilisearchFacetValue extends MeilisearchModel
{
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getValue(): string
    {
        return $this->data['value'];
    }

    public function getCount(): int
    {
        return $this->data['count'];
    }
}