<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Document;
use App\Services\AIClientInterface;
use App\Services\FakeOpenAIClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RagChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        //Bind fake AI client
        $this->app->bind(AIClientInterface::class, FakeOpenAIClient::class);
    }

    private function createDocumentWithChunk(string $content, array $embedding): Document
    {
        $document = Document::create([
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

    public function test_it_returns_answer_and_sources_from_rag_endpoint()
    {
        $ai = app(AIClientInterface::class);

        //Create document
        $this->createDocumentWithChunk(
            'Laravel is a PHP framework',
            $ai->embed('Laravel is a PHP framework'),
        );

        $this->createDocumentWithChunk(
            'Python is popular for AI',
            $ai->embed('Python is popular for AI')
        );

        $response = $this->postJson('/api/rag-chat', [
            'question' => 'What is Laravel?',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['question', 'answer', 'sources' => [['content', 'score']]]);
        $response->assertJsonFragment(['question' => 'What is Laravel?']);
    }
}
