<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;

class MeilisearchHealth extends MeilisearchModel
{
    public function __construct(MeilisearchResponse $data)
    {
        $this->data = $data->getData();
    }

    public function getStatus()
    {
        return $this->data['status'];
    }

    public function isAvailable()
    {
        return $this->data['status'] == 'available';
    }
}