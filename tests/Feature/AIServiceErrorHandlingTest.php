<?php

namespace Tests\Feature;

use App\Services\AIClientInterface;
use App\Services\AIService;
// use App\Services\OpenAIClient;
use App\Clients\OpenAIClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AIServiceErrorHandlingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.openai.key' => 'test-key']);
    }

    public function test_chat_returns_message_when_rate_limited()
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'type' => 'rate_limit_exceeded',
                    'message' => 'Too many requests.'
                ]
            ], 429),
        ]);

        $this->app->bind(AIClientInterface::class, OpenAIClient::class);

        $service = app(AIService::class);

        // $reply = $service->chat('Hello');
        $reply = $service->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertEquals('Too many requests — try again later.', $reply);
    }
}
