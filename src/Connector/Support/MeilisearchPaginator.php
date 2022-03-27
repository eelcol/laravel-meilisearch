<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use ArrayAccess;
use Countable;
use Eelcol\LaravelMeilisearch\Connector\Collections\MeilisearchQueryCollection;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use IteratorAggregate;

class MeilisearchPaginator implements Arrayable, ArrayAccess, Countable, IteratorAggregate
{
    protected MeilisearchQuery $query;

    protected MeilisearchQueryCollection $collection;

    protected LengthAwarePaginator $paginator;

    protected int $per_page;

    protected string $page_name;

    public function __construct(MeilisearchQuery $query)
    {
        $this->query = $query;
    }

    public function __call($method, $arguments)
    {
        // first check if the method exists on the collection
        // otherwise call on the paginator
        if (method_exists($this->collection, $method)) {
            return $this->collection->{$method}(...$arguments);
        }

        return $this->paginator->{$method}(...$arguments);
    }

    public function perPage(int $per_page): self
    {
        $this->per_page = $per_page;

        return $this;
    }

    public function pageName(string $page_name): self
    {
        $this->page_name = $page_name;

        return $this;
    }

    public function createPaginator(): self
    {
        // get current page
        $current_page = Paginator::resolveCurrentPage($this->page_name);
        $offset = ($this->per_page * ($current_page - 1));

        // set limit and offset
        $this->query->limit($this->per_page);
        $this->query->offset($offset);

        // get results
        $this->collection = $this->query->get();

        $this->paginator = Container::getInstance()->makeWith(LengthAwarePaginator::class, [
            'items' => $this->collection->all(),
            'total' => $this->collection->totalCount(),
            'perPage' => $this->per_page,
            'currentPage' => $current_page,
            'options' => [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $this->page_name,
            ]
        ]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->paginator->toArray();
    }

    public function count(): int
    {
        return $this->paginator->count();
    }

    public function offsetExists($key): bool
    {
        return $this->paginator->has($key);
    }

    public function offsetGet($key)
    {
        return $this->paginator->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->paginator->put($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->paginator->forget($key);
    }

    public function getIterator()
    {
        return $this->paginator->getIterator();
    }
}