<?php

namespace Eelcol\LaravelMeilisearch\Connector\Traits;

use Eelcol\LaravelMeilisearch\Exceptions\IndexAlreadyExists;
use Eelcol\LaravelMeilisearch\Exceptions\IndexNotFound;
use Eelcol\LaravelMeilisearch\Exceptions\MissingDocumentId;

trait HandlesErrors
{
    /**
     * @throws MissingDocumentId
     * @throws IndexNotFound
     * @throws IndexAlreadyExists
     */
    public function checkForErrors(): void
    {
        if ($this->isErrorIndexAlreadyExists()) {
            throw new IndexAlreadyExists($this->getErrorMessage());
        }

        if ($this->isErrorIndexNotFound()) {
            throw new IndexNotFound($this->getErrorMessage());
        }

        if ($this->isErrorMissingDocumentId()) {
            throw new MissingDocumentId($this->getErrorMessage());
        }
    }

    public function getError(): mixed
    {
        if (array_key_exists('message', $this->data) && array_key_exists('code', $this->data)) {
            return $this->data;
        }

        return $this->data['error'] ?? null;
    }

    public function getErrorMessage(): mixed
    {
        if (is_null($this->getError())) {
            return null;
        }

        return $this->getError()['message'] ?? null;
    }

    public function getErrorCode(): mixed
    {
        if (is_null($this->getError())) {
            return null;
        }

        return $this->getError()['code'] ?? null;
    }

    public function isErrorIndexAlreadyExists(): bool
    {
        return $this->getErrorCode() == 'index_already_exists';
    }

    public function isErrorIndexNotFound(): bool
    {
        return $this->getErrorCode() == 'index_not_found';
    }

    public function isErrorMissingDocumentId(): bool
    {
        return $this->getErrorCode() == 'missing_document_id';
    }
}