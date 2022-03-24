<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;

class MeilisearchHealth extends MeilisearchModel
{
    public function __construct(array $data)
    {
        $this->data = $data;
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