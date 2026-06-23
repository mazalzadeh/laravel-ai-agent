<?php

namespace App\Services;

use App\Models\EmbeddingCache;

class EmbeddingCacheService
{
    public function get(string $text, ?string $model = null): ?array
    {
        $hash = $this->hash($text, $model);

        $cached = EmbeddingCache::where('hash', $hash)->first();

        return $cached?->embedding;
    }

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

    private function hash(string $text, ?string $model = null): string
    {
        return hash('sha256', trim($text) . '|' . ($model ?? 'default'));
    }
}
