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

    public function offsetSet($offset, $value)
    {
        // cannot set
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        // cannot unset
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }
}