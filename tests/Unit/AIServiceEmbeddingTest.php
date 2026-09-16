<?php

use App\Clients\OpenAIClient;
use App\Services\AIClientInterface;
use App\Services\AIService;
use App\Services\FakeOpenAIClient;
use Tests\TestCase;

class AIServiceEmbeddingTest extends TestCase
{
    public function test_it_generates_embeddings()
    {
        $client = new FakeOpenAIClient();

        $service = new AIService($client);

        $embedding = $service->embed("hello world");

        $this->assertIsArray($embedding);
        $this->assertNotEmpty($embedding);
    }

    public function test_same_text_returns_same_embedding()
    {
        $client = new FakeOpenAIClient();

        $service = new AIService($client);

        $e1 = $service->embed("hello world");
        $e2 = $service->embed("hello world");

        $this->assertEquals($e1, $e2);
    }

    public function test_it_generates_embeddings_with_fake_client()
    {
        $service = app(AIService::class);

        $embedding = $service->embed("hello world");

        $this->assertIsArray($embedding);
        $this->assertGreaterThan(16, $embedding);
    }

    public function test_openai_returns_real_embedding(): void
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey || $apiKey === 'fake-key' || str_starts_with((string) $apiKey, 'fake')) {
            $this->markTestSkipped('Real OpenAI API key is not configured.');
        }

        $this->app->bind(AIClientInterface::class, OpenAIClient::class);

        $service = app(AIService::class);

        $embedding = $service->embed('hello world');

        $this->assertIsArray($embedding);
        $this->assertGreaterThan(1000, count($embedding));
    }
}
