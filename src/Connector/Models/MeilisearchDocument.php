<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;

class MeilisearchDocument extends MeilisearchModel
{
    public function __construct(?MeilisearchResponse $response = null)
    {
        if ($response) {
            $this->data = $response->getData();
        }
    }

    public static function fromArray(array $data): self
    {
        return (new self())->setData($data);
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}