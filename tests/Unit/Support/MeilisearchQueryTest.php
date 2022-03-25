<?php

namespace Eelcol\LaravelMeilisearch\Tests\Unit\Support;

use Eelcol\LaravelMeilisearch\Connector\Facades\MeilisearchQuery;
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

        $this->assertEquals(["('title' = 'iphone' OR 'title' = 'galaxy' OR 'title' = 'note')"], $filters);

        $filters = MeilisearchQuery::index('products')
            ->whereIn('title', ['iphone', 'galaxy', 'note'])
            ->getSearchFilters();

        $this->assertEquals(["('title' = 'iphone' OR 'title' = 'galaxy' OR 'title' = 'note')"], $filters);
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
}
