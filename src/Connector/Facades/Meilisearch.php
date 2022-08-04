<?php

namespace Eelcol\LaravelMeilisearch\Connector\Facades;

use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchDocumentsCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchIndexCollection;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchQueryCollection;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchConnector;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchHealth;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchIndexItem;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchTask;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Illuminate\Support\Facades\Facade;

/**
 * @method static MeilisearchIndexCollection getAllIndexes
 * @method static bool indexExists(string $index)
 * @method static MeilisearchIndexItem getIndexInformation(string $index)
 * @method static MeilisearchTask createIndex(string $index, string $primaryKey)
 * @method static MeilisearchTask deleteIndex(string $index)
 * @method static MeilisearchTask addDocument(string $index, mixed $data)
 * @method static MeilisearchTask addDocuments(string $index, mixed $data)
 * @method static MeilisearchDocumentsCollection getDocuments(string $index, array $query)
 * @method static MeilisearchDocument getDocument(string $index, int $id)
 * @method static MeilisearchQuery query(string $index)
 * @method static MeilisearchQueryCollection searchDocuments(MeilisearchQuery $query)
 * @method static MeilisearchTask updateFilterableAttributes(string $index, array $attributes)
 * @method static array getFilterableAttributes(string $index)
 * @method static MeilisearchTask|null syncFilterableAttributes(string $index, array $attributes)
 * @method static MeilisearchTask updateSearchableAttributes(string $index, array $attributes)
 * @method static array getSearchableAttributes(string $index)
 * @method static MeilisearchTask|null syncSearchableAttributes(string $index, array $attributes)
 * @method static MeilisearchTask updateSortableAttributes(string $index, array $attributes)
 * @method static array getSortableAttributes(string $index)
 * @method static MeilisearchTask|null syncSortableAttributes(string $index, array $attributes)
 *  * @method static MeilisearchTask updateRankingRules(string $index, array $attributes)
 * @method static array getRankingRules(string $index)
 * @method static MeilisearchTask|null syncRankingRules(string $index, array $attributes)
 * @method static MeilisearchTask setMaxTotalHits(string $index, int $max_total_hits)
 * @method static MeilisearchTask setMaxValuesPerFacet(string $index, int $max_values_per_facet)
 * @method static MeilisearchHealth getHealth()
 * @method static MeilisearchTask getTask(int $taskId)
 * @method static string getVersion()
 *
 * @see MeilisearchConnector
 */
class Meilisearch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'meilisearch';
    }
}
