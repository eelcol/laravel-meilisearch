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

    protected int $current_page;

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

        $return = $this->paginator->{$method}(...$arguments);

        if (is_object($return) && get_class($return) == get_class($this->paginator)) {
            // must be chainable
            return $this;
        }

        // return return-value from method call
        return $return;
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
        $this->current_page = Paginator::resolveCurrentPage($this->page_name);

        // set page number and num per page
        $this->query->page($this->current_page);
        $this->query->hitsPerPage($this->per_page);

        // build paginator
        $this->buildPaginator();

        return $this;
    }

    public function nextPage()
    {
        $this->current_page++;

        $this->query->page($this->current_page);

        // build paginator
        $this->buildPaginator();

        return $this;
    }

    public function currentPage(): int
    {
        return $this->query->getPage();
    }

    protected function buildPaginator()
    {
        // get results
        $this->collection = $this->query->get();

        $this->paginator = Container::getInstance()->makeWith(LengthAwarePaginator::class, [
            'items' => $this->collection->all(),
            'total' => $this->collection->totalCount(),
            'perPage' => $this->per_page,
            'currentPage' => $this->current_page,
            'options' => [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $this->page_name,
            ]
        ]);
    }

    public function toArray(): array
    {
        return $this->paginator->toArray();
    }

    public function count(): int
    {
        return $this->paginator->count();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->paginator->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->paginator->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->paginator->put($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->paginator->forget($offset);
    }

    public function getIterator(): \Traversable
    {
        return $this->paginator->getIterator();
    }

    public function getPaginator(): LengthAwarePaginator
    {
        return $this->paginator;
    }
}
