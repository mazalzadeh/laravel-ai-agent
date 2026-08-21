<?php

declare(strict_types=1);

namespace App\AI\Context;

use App\Services\AIService;
use App\Services\EmbeddingCacheService;
use App\Services\VectorSimilarityService;
use Illuminate\Support\Collection;

final class VectorContextRetriever implements ContextRetriever
{
    public function __construct(
        private readonly AIService $aiservice,
        private readonly EmbeddingCacheService $embeddingCache,
        private readonly VectorSimilarityService $vectorSimilarity,
    ) {}


    /**
     * Retrieve the most relevant documents for the given query.
     *
     * @return Collection<int, RetrievedDocument>
     */
    public function retrieve(string $query, int $limit = 5): Collection
    {
        $embedding = $this->embeddingCache->remember(
            $query,
            fn(): array => $this->aiservice->embed($query),
        );

        $similarResults = $this->vectorSimilarity->findMostSimilar(
            $embedding,
            $limit,
        );

        return collect($similarResults)
            ->map(
                fn(array $result): RetrievedDocument => $this->mapResult($result),
            )
            ->values();
    }


    /**
     * Convert a raw similarity result into a RetrievedDocument DTO.
     *
     * @param array{
     *     document: object,
     *     chunk: object|null,
     *     score: float|int
     * } $result
     */
    private function mapResult(array $result): RetrievedDocument
    {
        $document = $result['document'];
        $chunk = $result['chunk'];

        return new RetrievedDocument(
            documentId: (int) $document->getKey(),

            chunkId: $chunk?->getKey() !== null
                ? (int) $chunk->getKey()
                : null,

            chunkIndex: $chunk?->chunk_index !== null
                ? (int) $chunk->chunk_index
                : null,

            content: (string) ($chunk?->content ?? ''),

            score: (float) $result['score'],

            metadata: [
                'document_title' => $document->title ?? null,
                'document_source' => $document->source ?? null,
            ],
        );
    }
}
