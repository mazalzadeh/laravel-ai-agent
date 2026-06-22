<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SemanticSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createDocumentWithChunk(string $content, array $embedding): \App\Models\Document
    {
        $document = \App\Models\Document::create([
            'content' => $content,
            'embedding' => $embedding,
        ]);

        $document->chunks()->create([
            'chunk_index' => 0,
            'content' => $content,
            'embedding' => $embedding,
        ]);

        return $document;
    }

    public function test_semantic_search_returns_results()
    {
        $document = $this->createDocumentWithChunk(
            'Laravel is a PHP framework',
            array_fill(0, 16, 0.5)
        );

        $response = $this->postJson('/api/semantic-search', [
            'query' => 'PHP framework'
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'query',
            'results' => [
                [
                    'content',
                    'score',
                ],
            ],
        ]);
    }
}
