<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;
use Eelcol\LaravelMeilisearch\Exceptions\IndexAlreadyExists;
use Eelcol\LaravelMeilisearch\Exceptions\IndexNotFound;
use Eelcol\LaravelMeilisearch\Exceptions\MissingDocumentId;

class MeilisearchTask extends MeilisearchModel
{
    /**
     * @param array{uid: int, indexUid: string, status: string, type: string, details?: array, error?: array, duration?: string|null, enqueuedAt: string, startedAt: string, finishedAt: string} $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @throws IndexAlreadyExists
     * @throws IndexNotFound
     * @throws MissingDocumentId
     */
    public function checkStatus(): self
    {
        $status = Meilisearch::getTask($this->data['uid']);

        $this->data = $status->getData();

        if ($this->isFailed() && $this->isErrorIndexAlreadyExists()) {
            throw new IndexAlreadyExists($this->getErrorMessage());
        } elseif ($this->isFailed() && $this->isErrorIndexNotFound()) {
            throw new IndexNotFound($this->getErrorMessage());
        } elseif ($this->isFailed() && $this->isErrorMissingDocumentId()) {
            throw new MissingDocumentId($this->getErrorMessage());
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function isEnqueued(): bool
    {
        return $this->data['status'] == 'enqueued';
    }

    public function isProcessing(): bool
    {
        return $this->data['status'] == 'processing';
    }

    public function isSucceeded(): bool
    {
        return $this->data['status'] == 'succeeded';
    }

    public function isFailed(): bool
    {
        return $this->data['status'] == 'failed';
    }

    public function isNotSucceeded(): bool
    {
        return $this->data['status'] != 'succeeded';
    }

    public function getUid(): int
    {
        return $this->data['uid'];
    }

    public function getIndexUid(): string
    {
        return $this->data['indexUid'];
    }

    public function getStatus(): string
    {
        return $this->data['status'];
    }

    public function getType(): string
    {
        return $this->data['type'];
    }

    public function getDetails(): mixed
    {
        return $this->data['details'] ?? null;
    }

    public function getError(): mixed
    {
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
