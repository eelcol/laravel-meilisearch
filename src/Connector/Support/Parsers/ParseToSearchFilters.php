<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support\Parsers;

use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchQuery;
use Eelcol\LaravelMeilisearch\Exceptions\CannotParseWhereClause;

class ParseToSearchFilters
{
    public bool $query_for_metadata = false;

    public function __construct(
        protected MeilisearchQuery $query
    ) {}

    public function forMetadata()
    {
        $this->query_for_metadata = true;

        return $this;
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
    public function parseWhere(array $where): string
    {
        if (isset($where['column']) && isset($where['operator']) && isset($where['value'])) {
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
                return '';
            }

            return $this->parseMultipleWheres($where['wheres']);
        }

        throw new CannotParseWhereClause(json_encode($where));
    }

    protected function parseSimpleWhere(array $where): string
    {
        $value = $this->escapeValue($where['value']);

        return "'".$where['column']."' {$where['operator']} ".$value;
    }

    public function parseMultipleWheres(array $wheres): string
    {
        $expression = "(";
        foreach ($wheres as $where_index => $where) {
            $where_string = $this->parseWhere($where);

            if ($where_index > 0) {
                $expression .= " " . ($where['boolean'] ?? "AND") . " ";
            }

            $expression .= $where_string;
        }

        // close brackets
        $expression .= ")";

        return $expression;
    }

    protected function parseWhereWithArrayValue(array $where): string
    {
        // an array with values is supplied
        // this should result in a check if 1 of the values match
        // with OR statements (default)
        // with AND statements if the operator is "MATCHES"
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