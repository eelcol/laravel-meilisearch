<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support\Parsers;

use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Exceptions\CannotParseWhereClause;

class ParseToSearchFilters
{
    public bool $query_for_metadata = false;

    protected array $filter_columns = [];

    protected array $filter_columns_metadata = [];

    protected bool $inside_skip_for_facets = false;

    protected array $exclude_columns = [];

    public function __construct(
        protected MeilisearchQuery $query
    ) {}

    public function forMetadata()
    {
        $this->query_for_metadata = true;

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     * Excude 1 column from all WHERE clauses
     * Can be used to fetch correct meta data for example
     */
    public function excludeColumn(string $column): self
    {
        $this->exclude_columns = [$column];

        return $this;
    }

    protected function isColumnExcluded(string $column): bool
    {
        return in_array($column, $this->exclude_columns);
    }

    public function getFilterColumns(): array
    {
        // reset filter keys
        $this->filter_columns = [];
        $this->filter_columns_metadata = [];
        $wheres = $this->query->getWheres();

        foreach ($wheres as $where) {
            $this->parseWhere($where);
        }

        $this->filter_columns = array_unique($this->filter_columns);

        return [
            'query' => $this->filter_columns,
            'metadata' => $this->filter_columns_metadata
        ];
    }

    public function parse(): array
    {
        $filters = [];
        $wheres = $this->query->getWheres();

        foreach ($wheres as $where) {
            $expression = $this->parseWhere($where);
            if (!empty($expression)) {
                $filters[] = $expression;
            }
        }

        return $filters;
    }

    /**
     * @throws CannotParseWhereClause
     */
    public function parseWhere(array $where): ?string
    {
        if (isset($where['column']) && isset($where['operator']) && isset($where['value'])) {
            if ($this->isColumnExcluded($where['column'])) {
                return null;
            }

            if (is_array($where['value'])) {
                return $this->parseWhereWithArrayValue($where);
            }

            return $this->parseSimpleWhere($where);
        }

        if (isset($where['wheres']) && is_array($where['wheres'])) {
            // check if this expression should be skipped
            if (isset($where['skip_for_facets']) && $where['skip_for_facets'] === true && $this->query_for_metadata === true) {
                // query for getting the meta-data (facets count etc)
                // so skip this where clause
                return null;
            }

            if (isset($where['skip_for_facets']) && $where['skip_for_facets'] === true) {
                $this->inside_skip_for_facets = true;
            }

            $returnValue = $this->parseMultipleWheres($where['wheres']);

            if (isset($where['skip_for_facets']) && $where['skip_for_facets'] === true) {
                $this->inside_skip_for_facets = false;
            }

            return $returnValue;
        }

        throw new CannotParseWhereClause(json_encode($where));
    }

    protected function parseSimpleWhere(array $where): ?string
    {
        $value = $this->escapeValue($where['value']);

        if ($this->isColumnExcluded($where['column'])) {
            return null;
        }

        if ($this->inside_skip_for_facets) {
            $this->filter_columns_metadata[] = $where['column'];
        } else {
            $this->filter_columns[] = $where['column'];
        }

        return "'".$where['column']."' {$where['operator']} ".$value;
    }

    public function parseMultipleWheres(array $wheres): ?string
    {
        $expression = "(";
        foreach ($wheres as $where_index => $where) {
            $where_string = $this->parseWhere($where);
            if (empty($where_string)) {
                continue;
            }

            if ($where_index > 0) {
                $expression .= " " . ($where['boolean'] ?? "AND") . " ";
            }

            $expression .= $where_string;
        }

        if (strlen($expression) == 1) {
            return null;
        }

        // close brackets
        $expression .= ")";

        return $expression;
    }

    protected function parseWhereWithArrayValue(array $where): ?string
    {
        // an array with values is supplied
        // this should result in a check if 1 of the values match
        // with OR statements (default)
        // with AND statements if the operator is "MATCHES"
        if ($this->isColumnExcluded($where['column'])) {
            return null;
        }

        $boolean = "OR";
        if (isset($where['operator']) && strtolower($where['operator']) == "matches") {
            $boolean = "AND";
        }

        if ($boolean == "OR") {
            // use the IN operator
            $expression = "'" . $where['column'] . "' IN [";
            foreach ($where['value'] as $val) {
                $value = $this->escapeValue($val);
                $expression .= $value . ",";
            }

            $expression = substr($expression, 0, -1);
            $expression .= "]";
        } else {
            $expression = "(";
            foreach ($where['value'] as $val) {
                $value = $this->escapeValue($val);
                $expression .= "'" . $where['column'] . "' = " . $value . " " . $boolean . " ";
            }

            $expression = substr($expression, 0, (strlen($boolean) + 2) * -1);
            $expression .= ")";
        }

        if ($this->inside_skip_for_facets) {
            $this->filter_columns_metadata[] = $where['column'];
        } else {
            $this->filter_columns[] = $where['column'];
        }

        return $expression;
    }

    protected function escapeValue(mixed $value)
    {
        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        if (is_string($value)) {
            $value = "'" . $value . "'";
        }

        return $value;
    }
}