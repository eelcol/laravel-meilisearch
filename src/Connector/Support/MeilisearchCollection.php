<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use ArrayAccess;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * @mixin Collection
 */
class MeilisearchCollection implements IteratorAggregate, Countable, ArrayAccess
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

    public function all(): array
    {
        return $this->data->all();
    }

    public function offsetExists(mixed $offset): bool
    {
        return !is_null($this->data->get($offset));
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        //
    }

    public function offsetUnset(mixed $offset): void
    {
        //
    }
}