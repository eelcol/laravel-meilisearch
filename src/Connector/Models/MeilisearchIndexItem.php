<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use DateTime;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;
use MeiliSearch\Endpoints\Indexes;

class MeilisearchIndexItem extends MeilisearchModel
{
    /**
     * @param array{uid: string, createdAt: string, updatedAt: string, primaryKey: string} $data
     */
    public function __construct(
        protected array $data
    ) {
        //
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
        return self::parseDate($this->data['createdAt']);
    }

    public function getUpdatedAt(): DateTime
    {
        return self::parseDate($this->data['updatedAt']);
    }
}