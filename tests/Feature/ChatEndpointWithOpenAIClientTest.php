<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\AIClientInterface;
// use App\Services\OpenAIClient;
use App\Clients\OpenAIClient;

class ChatEndpointWithOpenAIClientTest extends TestCase
{
    public function test_chat_endpoint_uses_openai_client_and_returns_reply()
    {
        // این تست می‌خواهد مطمئن شود OpenAIClient واقعی استفاده می‌شود
        $this->app->bind(AIClientInterface::class, OpenAIClient::class);

        config()->set('services.openai.base_url', 'https://api.openai.com/v1');
        config()->set('services.openai.key', 'test-key');

        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl_fake',
                'choices' => [
                    [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'You said: Hello'
                        ]
                    ]
                ]
            ], 200),
        ]);

        $response = $this->postJson('/api/chat', ['message' => 'Hello']);

        $response->assertStatus(200)
            ->assertJson([
                'reply' => 'You said: Hello'
            ]);
    }
}
