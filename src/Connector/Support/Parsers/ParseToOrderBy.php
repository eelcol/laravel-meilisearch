<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support\Parsers;

use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Exceptions\CannotParseWhereClause;

class ParseToOrderBy
{
    public function __construct(
        protected MeilisearchQuery $query
    ) {}

    public function parse(): array
    {
        $ordering = [];
        $orders = $this->query->getOrderBy();

        foreach ($orders as $orderBy) {
            $ordering[] = $orderBy['field'] . ":" . $orderBy['order'];
        }

        return $ordering;
    }
}