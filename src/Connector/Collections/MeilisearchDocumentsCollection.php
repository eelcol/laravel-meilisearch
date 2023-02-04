<?php

namespace Eelcol\LaravelMeilisearch\Connector\Collections;

use Eelcol\LaravelMeilisearch\Connector\Facades\Meilisearch;
use Eelcol\LaravelMeilisearch\Connector\MeilisearchResponse;
use Eelcol\LaravelMeilisearch\Connector\Models\MeilisearchDocument;
use Eelcol\LaravelMeilisearch\Connector\Support\MeilisearchCollection;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<MeilisearchDocument>
 */
class MeilisearchDocumentsCollection extends MeilisearchCollection
{
    protected string $index;
    protected ?int $totalPages;
    protected ?int $currentPage;
    protected ?int $hitsPerPage;

    public function __construct(string $index, MeilisearchResponse $results)
    {
        $this->index = $index;
        $this->totalPages = $results->getTotalPages();
        $this->currentPage = $results->getCurrentPage();
        $this->hitsPerPage = $results->getHitsPerPage();

        $data = [];
        foreach ($results as $item) {
            $data[] = MeilisearchDocument::fromArray($item);
        }

        $this->data = collect($data);
        return $this->data;
    }

    public function hasNextPage(): bool
    {
        return $this->totalPages && $this->currentPage < $this->totalPages;
    }

    public function getNextPage(): self
    {
        return Meilisearch::getDocuments($this->index, [
            'page' => ($this->currentPage + 1),
            'hitsPerPage' => $this->hitsPerPage,
        ]);
    }
}