<?php

declare(strict_types=1);

namespace App\AI\Context;

final readonly class RetrievedDocument
{
    /**
     * @param int $documentId Identifier of the parent document.
     * @param int|null $chunkId Identifier of the retrieved chunk.
     * @param int|null $chunkIndex Position of the chunk inside the parent document.
     * @param string $content Retrieved chunk content.
     * @param float $score Relevance score.
     * @param array<string, mixed> $metadata Additional metadata for source tracking.
     */
    public function __construct(
        public int $documentId,
        public ?int $chunkId,
        public ?int $chunkIndex,
        public string $content,
        public float $score,
        public array $metadata = [],
    ) {}
}
