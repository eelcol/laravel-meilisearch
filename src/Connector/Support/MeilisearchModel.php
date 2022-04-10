<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use ArrayAccess;

class MeilisearchModel implements ArrayAccess
{
    protected array $data;

    public function __get($property)
    {
        return $this->data[$property] ?? null;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function offsetSet($offset, $value): void
    {
        // cannot set
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        // cannot unset
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }
}