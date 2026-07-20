<?php

namespace App\Services;

use App\Models\EmbeddingCache;

class EmbeddingCacheService
{
    /**
     * Retrieve a cached embedding array for a given text and model.
     *
     * This reduces latency and external API costs by serving previously
     * computed embeddings directly from the local database cache.
     *
     * @param string      $text  The input text whose embedding is being queried.
     * @param string|null $model The specific embedding model identifier used.
     *
     * @return array<int, float>|null The cached embedding vector, or null if not found.
     */
    public function get(string $text, ?string $model = null): ?array
    {
        $hash = $this->hash($text, $model);

        $cached = EmbeddingCache::where('hash', $hash)->first();

        return $cached?->embedding;
    }

    /**
     * Retrieve an embedding from cache or generate and store it if missing.
     *
     * This method implements a cache-aside strategy:
     * - Return the cached embedding if it exists.
     * - Otherwise, generate a new embedding using the provided callback,
     *   persist it to the cache, and return it.
     *
     * @param string   $text The input text used to compute the cache hash.
     * @param callable $callback A callback that generates the embedding array when missing.
     * @param string|null $model The embedding model identifier used in the cache key.
     *
     * @return array<int, float> The embedding vector.
     */
    public function remember(string $text, callable $callback, ?string $model = null): array
    {
        $hash = $this->hash($text, $model);

        $cached = EmbeddingCache::where('hash', $hash)->first();

        if ($cached) {
            return $cached->embedding;
        }

        $embedding = $callback();

        EmbeddingCache::create([
            'hash' => $hash,
            'text' => $text,
            'embedding' => $embedding,
            'model' => $model
        ]);

        return $embedding;
    }

    /**
     * Generate a stable hash for a text/model combination.
     *
     * The hash is used as the cache key for embedding records. Trimming the text
     * helps avoid duplicate cache entries caused by leading or trailing whitespace.
     *
     * @param string $text The input text to hash.
     * @param string|null $model The embedding model identifier, or null for default.
     *
     * @return string A SHA-256 hash string used as the cache key.
     */
    private function hash(string $text, ?string $model = null): string
    {
        return hash('sha256', trim($text) . '|' . ($model ?? 'default'));
    }
}
