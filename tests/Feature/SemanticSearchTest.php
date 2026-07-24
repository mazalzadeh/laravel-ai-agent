<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Models\DocumentChunk;
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

        $embedding = array_fill(0, 16, 0.5);

        $document = Document::factory()->create();

        $chunk = DocumentChunk::factory()->create([
            'document_id' => $document->id,
            'chunk_index' => 0,
            'content' => 'Laravel is a PHP framework',
            'embedding' => $embedding,
        ]);

        $this->assertEquals(1, DocumentChunk::count());
        $this->assertEquals('Laravel is a PHP framework', $chunk->content);

        $this->assertDatabaseCount('document_chunks', 1);

        $this->assertDatabaseHas('document_chunks', [
            'content' => 'Laravel is a PHP framework',
        ]);

        $response = $this->postJson('/api/semantic-search', [
            'query' => 'PHP framework'
        ]);

        $response->assertOk()->assertJsonPath('query', 'PHP framework');

        $results = $response->json('results');

        $this->assertNotEmpty($results, 'Results array is empty');

        $this->assertContains(
            'Laravel is a PHP framework',
            array_column($results, 'content')
        );
    }
}
