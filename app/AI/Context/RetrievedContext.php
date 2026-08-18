<?php

declare(strict_types=1);

namespace App\AI\Context;

use Illuminate\Support\Collection;

final readonly class RetrievedContext
{
    /**
     * @param string $content Formatted context string for prompt injection.
     * @param Collection<int, RetrievedDocument> $documents Retrieved documents collection.
     */
    public function __construct(
        public string $content,
        public Collection $documents,
    ) {}

    /**
     * Determine whether no documents were retrieved.
     */
    public function isEmpty(): bool
    {
        return $this->documents->isEmpty();
    }
}
