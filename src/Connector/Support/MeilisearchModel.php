<?php

namespace Eelcol\LaravelMeilisearch\Connector\Support;

use ArrayAccess;
use DateTime;
use Exception;

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

    public static function parseDate(?string $dateTime): ?DateTime
    {
        if (null === $dateTime) {
            return null;
        }

        try {
            return new DateTime($dateTime);
        } catch (\Exception $e) {
            // Trim 9th+ digit from fractional seconds. Meilisearch server can send 9 digits; PHP supports up to 8
            $trimPattern = '/(^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{1,8})(?:\d{1,})?(Z|[\+-]\d{2}:\d{2})$/';
            $trimmedDate = preg_replace($trimPattern, '$1$2', $dateTime);

            return new DateTime($trimmedDate);
        }
    }
}