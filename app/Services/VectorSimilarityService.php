<?php

namespace App\Services;

use App\Models\DocumentChunk;

class VectorSimilarityService
{
    /**
     * Calculate the cosine similarity between two numeric vectors.
     *
     * Cosine similarity measures the angle-based similarity between two vectors
     * and is commonly used in semantic search and embedding comparison.
     *
     * Formula:
     *   dot(a, b) / (||a|| * ||b||)
     *
     * @param array<int, float|int> $a The first vector.
     * @param array<int, float|int> $b The second vector.
     *
     * @return float A similarity score between -1 and 1.
     *
     * @throws \InvalidArgumentException If the vectors do not have the same length.
     */
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vector must have the same lenght');
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $normA += $value * $value;
            $normB += $b[$i] * $b[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Find the most similar document chunks for a query embedding.
     *
     * This method loads all document chunks, computes the cosine similarity
     * between the query embedding and each chunk embedding, then returns
     * the top-ranked results up to the specified limit.
     *
     * @param array<int, float|int> $queryEmbedding The embedding vector of the query.
     * @param int $limit The maximum number of similar results to return.
     *
     * @return \Illuminate\Support\Collection<int, array{
     *     document: \App\Models\Document,
     *     chunk: \App\Models\DocumentChunk,
     *     score: float
     * }>
     */
    public function findMostSimilar(array $queryEmbedding, int $limit = 5)
    {
        return DocumentChunk::with('document')
            ->get()
            ->map(function ($chunk) use ($queryEmbedding) {
                return [
                    'document' => $chunk->document,
                    'chunk' => $chunk,
                    'score' => $this->cosineSimilarity($queryEmbedding, $chunk->embedding),
                ];
            })->sortByDesc('score')->take($limit)->values();
    }
}
