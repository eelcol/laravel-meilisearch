<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * @mixin Collection
 */
class MeilisearchCollection implements IteratorAggregate, Countable
{
    protected Collection $data;

    public function __call(string $method, array $arguments)
    {
        return $this->data->{$method}(...$arguments);
    }

    public function getIterator(): Traversable
    {
        return $this->data->getIterator();
    }

    public function count(): int
    {
        return $this->data->count();
    }
}