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

    public function test_it_returns_answer_and_sources_from_rag_endpoint()
    {
        $ai = app(AIClientInterface::class);

        //Create document
        Document::create([
            'content' => 'Laravel is a PHP framework',
            'embedding' => $ai->embed('Laravel is a PHP framework'),
        ]);

        Document::create([
            'content' => 'Python is popular for AI',
            'embedding' => $ai->embed('Python is popular for AI'),
        ]);

        $response = $this->postJson('/api/rag-chat', [
            'question' => 'What is Laravel?',
        ]);

        $response->assertStatus(200)->assertJsonStructure(['question', 'answer', 'sources' => [['content', 'score']]]);
    }
}
