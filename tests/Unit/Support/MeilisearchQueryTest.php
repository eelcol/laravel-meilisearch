<?php

namespace Eelcol\LaravelMeilisearch\Tests\Unit\Support;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\Facades\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchConnector;
use Eelcol\LaravelMeilisearch\Exceptions\InvalidOrdering;
use Eelcol\LaravelMeilisearch\Exceptions\InvalidWhereBoolean;
use Eelcol\LaravelMeilisearch\Tests\TestCase;

class MeilisearchQueryTest extends TestCase
{
    public function testFacetsDistribution()
    {
        $facets = MeilisearchQuery::index('products')
            ->setFacets(['Color', 'Size'])
            ->getFacetsDistribution();

        $this->assertEquals(['Color', 'Size'], $facets);

        $facets = MeilisearchQuery::index('products')
            ->addFacet('Color')
            ->addFacet('Size')
            ->getFacetsDistribution();

        $this->assertEquals(['Color', 'Size'], $facets);
    }

    public function testSingleWheres()
    {
        $filters = MeilisearchQuery::index('products')
            ->where('title', '=', 'iphone')
            ->getSearchFilters();

        $this->assertEquals(["'title' = 'iphone'"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('price', '>', 10)
            ->getSearchFilters();

        $this->assertEquals(["'price' > 10"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('price', '<', 10)
            ->getSearchFilters();

        $this->assertEquals(["'price' < 10"], $filters);
    }

    public function testMultipleWheres()
    {
        $filters = MeilisearchQuery::index('products')
            ->where('title', '=', 'iphone')
            ->where('model', '=', 'se')
            ->getSearchFilters();

        $this->assertEquals(["'title' = 'iphone'", "'model' = 'se'"], $filters);
    }

    public function testWhereIn()
    {
        $filters = MeilisearchQuery::index('products')
            ->where('title', 'IN', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["'title' IN ['iphone','galaxy','note']"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereIn('title', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["'title' IN ['iphone','galaxy','note']"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereIn('title', [])
            ->getSearchFilters();

        $this->assertEquals([], $filters);
    }

    public function testWhereNotIn()
    {
        $filters = MeilisearchQuery::index('products')
            ->where('title', 'NOT IN', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["'title' NOT IN ['iphone','galaxy','note']"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereNotIn('title', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["'title' NOT IN ['iphone','galaxy','note']"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereNotIn('title', [])
            ->getSearchFilters();

        $this->assertEquals([], $filters);
    }

    public function testWhereMatches()
    {
        $filters = MeilisearchQuery::index('products')
            ->where('title', 'MATCHES', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["('title' = 'iphone' AND 'title' = 'galaxy' AND 'title' = 'note')"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereMatches('title', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["('title' = 'iphone' AND 'title' = 'galaxy' AND 'title' = 'note')"], $filters);
    }

    public function testWhereWithBooleans()
    {
        $filters = MeilisearchQuery::index('products')
            ->where('available', '=', true)
            ->getSearchFilters();

        $this->assertEquals(["'available' = true"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('available', '=', false)
            ->getSearchFilters();

        $this->assertEquals(["'available' = false"], $filters);
    }

    public function testWhereEmpty()
    {
        $filters = MeilisearchQuery::index('products')
            ->whereIsEmpty('brand')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS EMPTY"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereNotEmpty('brand')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS NOT EMPTY"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('brand', 'IS EMPTY')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS EMPTY"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('brand', 'IS NOT EMPTY')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS NOT EMPTY"], $filters);
    }

    public function testWhereNull()
    {
        $filters = MeilisearchQuery::index('products')
            ->whereNull('brand')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS NULL"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereNotNull('brand')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS NOT NULL"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('brand', 'IS NULL')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS NULL"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->where('brand', 'IS NOT NULL')
            ->getSearchFilters();

        $this->assertEquals(["'brand' IS NOT NULL"], $filters);
    }

    public function testItThrowsAnExceptionWhenUsingIncorrectBoolean()
    {
        $this->expectException(InvalidWhereBoolean::class);

        MeilisearchQuery::index('products')
            ->where('title', '=', 'iphone', 'xyz');
    }

    public function testSimpleOrWhere()
    {
        $filters = MeilisearchQuery::index('products')
            ->where(function ($q) {
                $q->orWhere('category', '=', 'phones');
                $q->orWhere('category', '=', 'computers');
            })
            ->getSearchFilters();

        $this->assertEquals(["('category' = 'phones' OR 'category' = 'computers')"], $filters);
    }

    public function testSingleOrderBy()
    {
        $orderby = MeilisearchQuery::index('products')
            ->orderBy('title')
            ->getSearchOrdering();

        $this->assertEquals(['title:asc'], $orderby);

        $orderby = MeilisearchQuery::index('products')
            ->orderBy('title', 'asc')
            ->getSearchOrdering();

        $this->assertEquals(['title:asc'], $orderby);

        $orderby = MeilisearchQuery::index('products')
            ->orderBy('title', 'desc')
            ->getSearchOrdering();

        $this->assertEquals(['title:desc'], $orderby);

        $orderby = MeilisearchQuery::index('products')
            ->orderByDesc('title')
            ->getSearchOrdering();

        $this->assertEquals(['title:desc'], $orderby);
    }

    public function testMultipleOrderBy()
    {
        $orderby = MeilisearchQuery::index('products')
            ->orderBy('title')
            ->orderBy('price')
            ->getSearchOrdering();

        $this->assertEquals(['title:asc', 'price:asc'], $orderby);
    }

    public function testItThrowsAnExceptionWhenOrderingIncorrect()
    {
        $this->expectException(InvalidOrdering::class);

        MeilisearchQuery::index('products')->orderBy('title', 'abc');
    }

    public function testAnotherQueryForMetadata()
    {
        $query = MeilisearchQuery::index('products')
            ->where('category', '=', 'phones')
            ->keepFacetsInMetadata(function ($q) {
                $q->where('color', '=', 'black');
            });

        $this->assertEquals(["'category' = 'phones'", "('color' = 'black')"], $query->getSearchFilters());
        $this->assertEquals(["'category' = 'phones'"], $query->getSearchFiltersForMetadata());
    }

    public function testQueryDeletesDocuments()
    {
        Meilisearch::shouldReceive('deleteFromQuery')
            ->withArgs(function ($args) {
                $builder = $args->test();

                return $builder['query'] == ""
                    && $builder['filters'] == ["'brand' = 'meilisearch'"]
                    && $builder['filters_for_metadata'] == ["'brand' = 'meilisearch'"]
                    && $builder['ordering'] == []
                    && $builder['facets'] == []
                    && is_null($builder['page'])
                    && is_null($builder['perPage'])
                ;

            })
            ->once();

        MeilisearchQuery::index('products')
            ->where('brand', '=', 'meilisearch')
            ->delete();
    }
}
