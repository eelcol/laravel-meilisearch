<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use DateTime;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;
use MeiliSearch\Endpoints\Indexes;

class MeilisearchIndexItem extends MeilisearchModel
{
    public function __construct(Indexes $data)
    {
        $this->data = [
            'uid' => $data->getUid(),
            'primaryKey' => $data->getPrimaryKey(),
            'createdAt' => $data->getCreatedAt(),
            'updatedAt' => $data->getUpdatedAt()
        ];
    }

    public function getUid(): string
    {
        return $this->data['uid'];
    }

    public function getPrimaryKey(): string
    {
        return $this->data['primaryKey'];
    }

    public function getCreatedAt(): DateTime
    {
        return $this->data['createdAt'];
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->data['updatedAt'];
    }
}