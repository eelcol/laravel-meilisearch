<?php

namespace Eelcol\LaravelMeilisearch\Connector\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery index(string $index)
 *
 * @see \Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery
 */
class MeilisearchQuery extends Facade
{
    protected static function getFacadeAccessor()
    {
        // facades use singletons by default
        // bypass this to get a new instance everytime
        self::clearResolvedInstance('meilisearch-query');

        return 'meilisearch-query';
    }
}