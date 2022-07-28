<?php

namespace Eelcol\LaravelMeilisearch\Connector\Models;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchModel;
use Eelcol\LaravelMeilisearch\Connector\Traits\HandlesErrors;
use Eelcol\LaravelMeilisearch\Exceptions\IndexAlreadyExists;
use Eelcol\LaravelMeilisearch\Exceptions\IndexNotFound;
use Eelcol\LaravelMeilisearch\Exceptions\MissingDocumentId;

class MeilisearchTask extends MeilisearchModel
{
    use HandlesErrors;

    public function __construct(?MeilisearchResponse $response = null)
    {
        if (!is_null($response)) {
            /** @var array{taskUid?: int, uid?: int, indexUid: string, status: string, type: string, details?: array, error?: array, duration?: string|null, enqueuedAt: string, startedAt: string, finishedAt: string} $this- >data */
            $this->data = $response->getData();
        }
    }

    public static function fromArray(array $data): self
    {
        return (new self())->setData($data);
    }

    /**
     * @throws IndexAlreadyExists
     * @throws IndexNotFound
     * @throws MissingDocumentId
     */
    public function checkStatus(): self
    {
        $status = Meilisearch::getTask($this->getUid());

        $this->data = $status->getData();

        if ($this->isFailed()) {
            $this->checkForErrors();
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
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
        return $this->data['taskUid'] ?? $this->data['uid'];
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
}
