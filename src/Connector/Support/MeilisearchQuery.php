<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use Closure;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchQueryCollection;
use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\Support\Parsers\ParseToOrderBy;
use Eelcol\LaravelMeilisearch\Connector\Support\Parsers\ParseToSearchFilters;
use Eelcol\LaravelMeilisearch\Enums\QueryOrderBy;
use Eelcol\LaravelMeilisearch\Enums\QueryWhereBoolean;
use Eelcol\LaravelMeilisearch\Exceptions\CannotOrderAfterRandomOrder;
use Eelcol\LaravelMeilisearch\Exceptions\IndexNotSupplied;
use Eelcol\LaravelMeilisearch\Exceptions\InvalidOrdering;
use Eelcol\LaravelMeilisearch\Exceptions\InvalidWhereBoolean;
use Eelcol\LaravelMeilisearch\Exceptions\OrWhereTopLevelNotSupported;
use Illuminate\Support\Arr;

class MeilisearchQuery
{
    protected string $index;

    protected string $search = '';

    protected array $wheres = [];

    protected array $orderBy = [];

    protected array $facets = [];

    protected int $limit = 20;

    protected int $offset = 0;

    protected ?int $page = null;

    protected ?int $hitsPerPage = null;

    protected bool $in_random_order = false;

    protected bool $separate_query_for_metadata = false;

    protected string $castAs;

    protected bool $inside_where_closure = false;

    public function __construct(string $index = '')
    {
        $this->index = $index;
    }

    public function index(string $index): self
    {
        $this->index = $index;

        return $this;
    }

    public function castAs(string $class): self
    {
        $this->castAs = $class;

        return $this;
    }

    public function search(string $search = ''): self
    {
        $this->search = $search;

        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = $limit;

        if (!is_null($offset)) {
            $this->offset = $offset;
        }

        return $this;
    }

    public function page(int $page): self
    {
        $this->page = $page;
        $this->in_random_order = false;

        return $this;
    }

    public function hitsPerPage(int $hitsPerPage): self
    {
        $this->hitsPerPage = $hitsPerPage;

        return $this;
    }

    /**
     * @param \Closure|string $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return $this
     * @throws InvalidWhereBoolean
     */
    public function where(mixed $column, ?string $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if (!QueryWhereBoolean::tryFrom($boolean)) {
            throw new InvalidWhereBoolean($boolean);
        }

        if (is_a($column, Closure::class)) {
            $query = $this->newQuery()->insideWhereClosure();

            $column($query);

            $this->wheres[] = [
                'wheres' => $query->getWheres(),
                'boolean' => $boolean,
            ];

            return $this;
        }

        if (strtolower($operator) == "in" && !is_array($value)) {
            $value = Arr::wrap($value);
        }

        $this->wheres[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        return $this;
    }

    public function keepFacetsInMetadata(Closure $closure): self
    {
        $this->separate_query_for_metadata = true;

        $query = $this->newQuery();

        $closure($query);

        $this->wheres[] = [
            'wheres' => $query->getWheres(),
            'skip_for_facets' => true
        ];

        return $this;
    }

    /**
     * @param string $column
     * @param array $values
     * @return $this
     * @throws InvalidWhereBoolean
     * A document gets returned when the column matches at least 1 of the values
     */
    public function whereIn(string $column, array $values): self
    {
        if (count($values) > 0) {
            $this->where($column, 'IN', $values);
        }

        return $this;
    }

    /**
     * @param string $column
     * @param array $values
     * @return $this
     * * A document gets returned when the column matches ALL values
     */
    public function whereMatches(string $column, array $values): self
    {
        return $this->where($column, 'MATCHES', $values);
    }

    /**
     * @throws OrWhereTopLevelNotSupported
     * @throws InvalidWhereBoolean
     */
    public function orWhere(mixed $column, ?string $operator = null, mixed $value = null): self
    {
        if (!$this->isInsideWhereClosure()) {
            throw new OrWhereTopLevelNotSupported();
        }

        return $this->where($column, $operator, $value, 'OR');
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @param string|array $field
     * @param string $order
     * @return $this
     * @throws CannotOrderAfterRandomOrder
     * @throws InvalidOrdering
     */
    public function orderBy(mixed $field, string $order = 'asc'): self
    {
        if ($this->in_random_order === true) {
            // cannot order on another field, after ordering random!
            throw new CannotOrderAfterRandomOrder();
        }

        if (!QueryOrderBy::tryFrom($order)) {
            throw new InvalidOrdering($order);
        }

        $fields = Arr::wrap($field);

        foreach ($fields as $f) {
            $this->orderBy[] = [
                'field' => $f,
                'order' => $order,
            ];
        }

        return $this;
    }

    public function inRandomOrder(): self
    {
        $this->in_random_order = true;

        return $this;
    }

    public function orderByDesc(mixed $field): self
    {
        return $this->orderBy($field, 'desc');
    }

    public function setFacets(array $facets): self
    {
        $this->facets = $facets;

        return $this;
    }

    public function addFacet(string $facet): self
    {
        $this->facets[] = $facet;

        return $this;
    }

    /**
     * @return $this
     */
    protected function newQuery(): self
    {
        return new self($this->index);
    }

    /**
     * @return $this
     * Method to indicate the querybuilder is used inside a closure
     */
    public function insideWhereClosure(): self
    {
        $this->inside_where_closure = true;

        return $this;
    }

    public function isInsideWhereClosure(): bool
    {
        return $this->inside_where_closure;
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getSearchQuery(): string
    {
        return $this->search;
    }

    public function getSearchFilters(): array
    {
        return (new ParseToSearchFilters($this))->parse();
    }

    public function getSearchFiltersExcludeColumn(string $column): array
    {
        return (new ParseToSearchFilters($this))->excludeColumn($column)->parse();
    }

    public function getSearchFiltersForMetadata(): array
    {
        return (new ParseToSearchFilters($this))->forMetadata()->parse();
    }

    public function getSearchFilterColumnsForMetadata(): array
    {
        return (new ParseToSearchFilters($this))->getFilterColumns()['metadata'];
    }

    public function getFacetsDistribution(): array
    {
        return $this->facets;
    }

    public function getSearchOrdering(): array
    {
        return (new ParseToOrderBy($this))->parse();
    }

    public function shouldOrderRandomly(): bool
    {
        return $this->in_random_order;
    }

    public function shouldQueryForMetadata(): bool
    {
        return $this->separate_query_for_metadata;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getSearchLimit(): int
    {
        return $this->limit;
    }

    public function getSearchOffset(): int
    {
        return $this->offset;
    }

    public function getHitsPerPage(): ?int
    {
        return $this->hitsPerPage;
    }

    public function when($expression, Closure $closure): self
    {
        if ($expression === true) {
            $closure($this);
        }

        return $this;
    }

    /**
     * @throws IndexNotSupplied
     */
    public function get(): MeilisearchQueryCollection
    {
        if (empty($this->index)) {
            throw new IndexNotSupplied();
        }

        $documents = Meilisearch::searchDocuments($this);

        if (isset($this->castAs)) {
            $documents->castAs($this->castAs);
        }

        return $documents;
    }

    public function paginate(int $per_page, string $page_name = 'page'): MeilisearchPaginator
    {
        return (new MeilisearchPaginator($this))
            ->perPage($per_page)
            ->pageName($page_name)
            ->createPaginator();
    }

    public function getMeilisearchDataForMetadataQueries(): array
    {
        /**
         * Fetch the columns on which the data is filtering
         * now perform a query for meta-data for each of the columns
         * change the search query accordingly.
         * For example: the query filters on color=black, size=M, filter category not used
         * - query for filters 'size' and 'category' with 'color=black'
         * - query for filters 'color' and 'category' with 'size=M'
         */
        $searchColumns = $this->getSearchFilterColumnsForMetadata();

        $queries = [];

        foreach ($searchColumns as $searchColumn) {
            $queries[] = [
                    'indexUid' => $this->getIndex(),
                    'q' => $this->getSearchQuery(),
                    'limit' => 1,
                ] + [
                    'filter' => $this->getSearchFiltersExcludeColumn($searchColumn),
                    'facets' => [$searchColumn],
                    'sort' => $this->getSearchOrdering(),
                ];
        }

        return $queries;
    }

    public function getMeilisearchDataForMainQuery(): array
    {
        $data = [
            'q' => $this->getSearchQuery()
        ] + [
            'filter' => $this->getSearchFilters(),
            'facets' => $this->getFacetsDistribution(),
            'sort' => $this->getSearchOrdering(),
        ];

        if (is_null($this->page)) {
            $data['limit'] = $this->getSearchLimit();
            $data['offset'] = $this->getSearchOffset();
        } else {
            $data['page'] = $this->getPage();
            $data['hitsPerPage'] = $this->getHitsPerPage();
        }

        return $data;
    }

    public function dd()
    {
        dd([
            'query' => $this->getSearchQuery(),
            'filters' => $this->getSearchFilters(),
            'filters_for_metadata' => $this->getSearchFiltersForMetadata(),
            'ordering' => $this->getSearchOrdering(),
            'facets' => $this->getFacetsDistribution(),
            'page' => $this->getPage(),
            'perPage' => $this->getHitsPerPage(),
        ]);
    }
}
