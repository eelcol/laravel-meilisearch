<?php

namespace Eelcol\LaravelMeilisearch\Connector;

use ArrayAccess;
use Countable;
use Eelcol\LaravelMeilisearch\Connector\Traits\HandlesErrors;
use Eelcol\LaravelMeilisearch\Exceptions\IndexAlreadyExists;
use Eelcol\LaravelMeilisearch\Exceptions\IndexNotFound;
use Eelcol\LaravelMeilisearch\Exceptions\MissingDocumentId;
use Illuminate\Http\Client\Response;
use IteratorAggregate;
use ReturnTypeWillChange;

class MeilisearchResponse implements ArrayAccess, IteratorAggregate, Countable
{
    use HandlesErrors;

    protected array $data;

    /**
     * @throws MissingDocumentId
     * @throws IndexAlreadyExists
     * @throws IndexNotFound
     */
    public function __construct(
        protected Response $response
    ) {
        $this->data = $this->response->json();

        if (array_key_exists('results', $this->data)) {
            $this->data = $this->data['results'];
        }

        if (array_key_exists('message', $this->data) && array_key_exists('code', $this->data)) {
            $this->checkForErrors();
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function getOffset(): ?int
    {
        return $this->response->json('offset');
    }

    public function getLimit(): ?int
    {
        return $this->response->json('limit');
    }

    public function getTotal(): ?int
    {
        return $this->response->json('total');
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]) || \array_key_exists($offset, $this->data);
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        return null;
    }

    public function count(): int
    {
        return count($this->data);
    }
}