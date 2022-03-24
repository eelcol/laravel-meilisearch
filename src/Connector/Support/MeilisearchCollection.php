<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use ArrayIterator;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * @mixin Collection
 */
class MeilisearchCollection implements IteratorAggregate
{
    protected Collection $data;

    public function __call(string $method, array $arguments)
    {
        return $this->data->{$method}(...$arguments);
    }

    public function getIterator(): Traversable
    {
        return $this->data;
    }
}