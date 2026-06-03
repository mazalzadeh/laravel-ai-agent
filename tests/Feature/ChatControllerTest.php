<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\AIService;
use Mockery;

class ChatControllerTest extends TestCase
{
    public function test_it_stream_sse_response(): void
    {
        $messages = [['role' => 'user', 'content' => 'Hello']];

        $service = Mockery::mock(AIService::class);

        $service->shouldReceive('streamChat')
            ->once()
            ->with($messages)
            ->andReturn($this->fakeStream());

        $this->app->instance(AIService::class, $service);

        $response = $this->postJson('/api/chat/stream', ['messages' => $messages]);

        $response->assertOk();

        // $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));
        $response->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('data: {"content":"Hello"}', $content);
        // $this->assertMatchesRegularExpression('/data: {"content":"Hello"}/', $content);
        $this->assertStringContainsString('data: {"content":" world"}', $content);
        $this->assertStringContainsString('data: [DONE]', $content);
    }

    private function fakeStream(): \Generator
    {
        yield 'Hello';
        yield ' world';
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
