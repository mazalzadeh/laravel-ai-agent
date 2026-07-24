<?php

namespace Tests\Feature;

use App\Services\EmbeddingCacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmbeddingCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_caches_embedding_and_does_not_generate_it_again(): void
    {
        $service = app(EmbeddingCacheService::class);

        $calls = 0;

        $text = 'Laravel RAG embedding cache test';
        $expectedEmbedding = [0.1, 0.2, 0.3];

        $firstEmbedding = $service->remember(
            $text,
            function () use (&$calls, $expectedEmbedding) {
                $calls++;

                return $expectedEmbedding;
            },
            'test-model'
        );

        $secondEmbedding = $service->remember(
            $text,
            function () use (&$calls) {
                $calls++;
                return [9.9, 9.9, 9.9];
            },
            'test-model'
        );

        $this->assertSame($expectedEmbedding, $firstEmbedding);
        $this->assertSame($expectedEmbedding, $secondEmbedding);
        $this->assertSame(1, $calls);

        $this->assertDatabaseCount('embedding_caches', 1);

        $this->assertDatabaseHas('embedding_caches', [
            'text' => $text,
            'model' => 'test-model',
        ]);
    }

    public function test_same_text_with_different_model_creates_seperate_cache_record(): void
    {
        $service = app(EmbeddingCacheService::class);

        $text = 'Same text but different embedding model';

        $service->remember($text, fn() => [0.1, 0.2], 'model-a');
        $service->remember($text, fn() => [0.3, 0.4], 'model-b');

        $this->assertDatabaseCount('embedding_caches', 2);

        $this->assertDatabaseHas('embedding_caches', [
            'text' => $text,
            'model' => 'model-a',
        ]);

        $this->assertDatabaseHas('embedding_caches', [
            'text' => $text,
            'model' => 'model-b',
        ]);
    }
}
