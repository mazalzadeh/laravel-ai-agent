<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Document;
use App\Models\DocumentChunk;
use App\Models\EmbeddingCache;
use App\Services\RagService;
use App\Services\AIService;

class RagEmbeddingCacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rag_pipeline_uses_embedding_cache()
    {
        $question = 'What is Laravel?';

        //Fake AIService
        /*$ai = new class {
            public int $embedCalls = 0;

            public function embed(string $text): array
            {
                $this->embedCalls++;

                // deterministic vector
                return [0.1, 0.2, 0.3];
            }

            public function chat(array $messages): string
            {
                return 'Laravel is a PHP framework';
            }
        };*/
        $ai = \Mockery::mock(\App\Services\AIService::class);

        $ai->shouldReceive('embed')->once()->andReturn([0.1, 0.2, 0.3]);

        $ai->shouldReceive('chat')->twice()->andReturn('Laravel is a PHP framework.');

        $this->app->instance(\App\Services\AIService::class, $ai);

        $this->app->instance(AIService::class, $ai);

        //Create document+chunk
        $doc = Document::create([
            'content' => 'Laravel is a PHP framework for web development.',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        DocumentChunk::create([
            'document_id' => $doc->id,
            'chunk_index' => 0,
            'content' => 'Laravel is a PHP framework for web development.',
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $rag = app(RagService::class);

        //First call
        $rag->answer($question);

        //Second call(should hit cache)
        $rag->answer($question);

        //Cache should contain one record
        $this->assertDatabaseCount('embedding_caches', 1);
    }
}
