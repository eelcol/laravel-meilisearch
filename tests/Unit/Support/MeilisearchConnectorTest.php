<?php

namespace Eelcol\LaravelMeilisearch\Tests\Unit\Support;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchConnector;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class MeilisearchConnectorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // additional setup
        app()->instance(
            'meilisearch',
            new MeilisearchConnector(['host' => 'http://meilisearch:7700', 'key' => 123])
        );
    }

    public function testSearchForFacetValues()
    {
        Http::fake([
            'http://meilisearch:7700/indexes/*/facet-search' => Http::response(
                ['facetHits' => [['value' => 'Amazon', 'count' => 100], ['value' => 'Nika', 'count' => 200]]],
                200
            ),
            '*' => Http::response([], 500),
        ]);

        $response = Meilisearch::searchFacetValues("products", "brand", "a");

        Http::assertSent(function (Request $request) {
            return $request->url() == "http://meilisearch:7700/indexes/products/facet-search"
                && $request->data() == ['facetName' => 'brand', 'facetQuery' => 'a'];
        });

        $this->assertEquals(2, $response->count());
        $this->assertEquals('Amazon', $response[0]->getValue());
        $this->assertEquals('Nika', $response[1]->getValue());
    }

    public function testSearchableAttributes()
    {
        Http::fake([
            'http://meilisearch:7700/indexes/*/search' => Http::response(
                ['hits' => [['id' => 1, 'title' => 'Nike Air 123'], ['id' => 2, 'title' => 'Nike Air M']]],
                200
            ),
            '*' => Http::response([], 500),
        ]);
        
        $mockQuery = $this->mock(MeilisearchQuery::class);
        $mockQuery->shouldReceive('shouldOrderRandomly')->andReturn(false);
        $mockQuery->shouldReceive('shouldQueryForMetadata')->andReturn(false);
        $mockQuery->shouldReceive('getIndex')->andReturn('products');
        $mockQuery->shouldReceive('getMeilisearchDataForMainQuery')->andReturn([
            'q' => 'Nike Air',
            'attributesToSearchOn' => ['title', 'description']
        ]);

        $response = Meilisearch::searchDocuments($mockQuery);

        Http::assertSent(function (Request $request) {
            return $request->url() == "http://meilisearch:7700/indexes/products/search"
                && $request->data()['q'] == 'Nike Air'
                && $request->data()['attributesToSearchOn'] == ['title','description'];
        });

        $this->assertEquals(2, $response->count());
    }
}