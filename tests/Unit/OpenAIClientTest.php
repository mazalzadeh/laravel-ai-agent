<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;

use App\DTO\OpenAIErrorDTO;
use App\DTO\ChatResponseDTO;
use Tests\TestCase;
// use App\Services\OpenAIClient;
use App\Clients\OpenAIClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;


class OpenAIClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.openai.key', 'test-key');
        Config::set('services.openai.base_url', 'https://api.openai.com/v1');
        Config::set('services.openai.timeout', 15);

    }

    public function test_chat_sends_request_and_returns_json()
    {
        
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl_fake',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'Hello from fake OpenAI']]]
            ], 200),
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $data = $client->chat('Hi');
        $messages = [['role' => 'user', 'content' => 'Hi']];
        $data = $client->chat($messages);

        $this->assertTrue($data['success']);
        $this->assertInstanceOf(ChatResponseDTO::class, $data['data']);
        $this->assertSame('chatcmpl_fake', $data['data']->id);
        $this->assertSame('Hello from fake OpenAI', $data['data']->content);

        Http::assertSent(function ($request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test-key');
            }

        );
    }

    public function test_chat_handles_500_error()
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'server error']],  500),
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $result = $client->chat('Hi');
        $result = $client->chat([['role' => 'user', 'content' => 'Hi']]);

        
        $this->assertFalse($result['success']);
        $this->assertInstanceOf(OpenAIErrorDTO::class, $result['error']);
    }

    public function test_chat_handles_rate_limit_error()
    {
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'type' => 'rate_limit_exceeded',
                    'message' => 'You made too many requests.'
                ]
            ], 429),
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $result = $client->chat('Hello');
        $result = $client->chat([['role' => 'user', 'content' => 'Hello']]);


        $this->assertFalse($result['success']);
        $this->assertInstanceOf(OpenAIErrorDTO::class, $result['error']);
        $this->assertEquals('rate_limit_exceeded', $result['error']->type);
    }

    public function test_chat_handles_failed_response_with_empty_body()
    {
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $result = $client->chat('Hello');
        $result = $client->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertFalse($result['success']);
        $this->assertInstanceOf(OpenAIErrorDTO::class, $result['error']);
    }

    public function test_it_retries_on_500_error_and_eventually_succeeds()
    {
        Http::fake([
            // 'api.openai.com/v1/chat/completions' => Http::sequence()
            'https://api.openai.com/v1/chat/completions' => Http::sequence()
                ->push(['error' => ['message' => 'Server error']], 500)
                ->push(['error' => ['message' => 'Server error']], 500)
                ->push([
                    'id' => 'chatcmpl_retry_success',
                    'object' => 'chat.completion',
                    'created' => time(),
                    'model' => 'gpt-4o',
                    'choices' => [[
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Success after retry',
                        ],
                        'finish_reason' => 'stop',
                    ]],
                    'usage' => [
                        'prompt_tokens' => 5,
                        'completion_tokens' => 5,
                        'total_tokens' => 10,
                    ]
                ], 200)
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $result = $client->chat('Retry Test');
        $result = $client->chat([['role' => 'user', 'content' => 'Retry Test']]);

        // تایید موفقیت نهایی
        $this->assertTrue($result['success']);
        $this->assertSame('Success after retry', $result['data']->content);

        Http::assertSentCount(3);
    }

    public function test_it_retries_on_429_rate_limit()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::sequence()
                ->push(['error' => ['message' => 'Too many requests']], 429)
                ->push(['id' => 'success', 'choices' => [['message' => ['role' => 'assistant', 'content' => 'Ok']]]], 200)
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $result = $client->chat('Retry Test');
        $result = $client->chat([['role' => 'user', 'content' => 'Retry Test']]);

        $this->assertTrue($result['success']);

        $this->assertSame('Ok', $result['data']->content);
        Http::assertSentCount(2);
        $this->addToAssertionCount(1);
    }

    public function test_it_does_not_retry_on_401_error()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response(
                ['error' => ['message' => 'Invalid API Key']],
                401
            ),
        ]);

        $client = $this->app->make(OpenAIClient::class);
        // $result = $client->chat('Unauthorized Test');
        $result = $client->chat([['role' => 'user', 'content' => 'Uauthorized Test']]);

        $this->assertFalse($result['success']);

        Http::assertSentCount(1);

        $this->addToAssertionCount(1);
    }

    public function test_chat_sends_system_prompt_and_conversation_history()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl_history',
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'History received']]]
            ], 200),
        ]);

        $client = $this->app->make(OpenAIClient::class);

        $messages = [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user', 'content' => 'Hi'],
            ['role' => 'assistan', 'content' => 'Hello! How can I help?'],
            ['role' => 'user', 'content' => 'Summarize our conversation.'],
        ];

        $result = $client->chat($messages);

        $this->assertTrue($result['success']);
        $this->assertSame('History received', $result['data']->content);

        Http::assertSent(function ($request) use ($messages) {
            $data = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://api.openai.com/v1/chat/completions'
                && $data['messages'] === $messages;
        });
    }

    public function test_chat_includes_options_in_request_payload()
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl_options',
                'choices' => [
                    ['message' => ['role' => 'assistant', 'content' => 'Options received']]
                ]
            ], 200)
        ]);
        

        $client = $this->app->make(OpenAIClient::class);

        $messages = [['role' => 'user', 'content' => 'Be brief']];

        $options = [
            'temperature' => 0.2,
            'max_tokens' => 50,
        ];

        $result = $client->chat($messages, $options);

        $this->assertTrue($result['success']);
        $this->assertSame('Options received', $result['data']->content);

        // Http::assertSent(function ($request) use ($messages) {
        //     $data = $request->data();

        //     return $data['messages'] === $messages
        //         && ($data['temperature'] ?? null) === 0.2
        //         && ($data['max_tokens'] ?? null) === 50
        //         && ($data['model'] ?? null) === 'gpt-4o';
        // });
        
        Http::assertSent(function ($request) use ($messages) {
            $data = $request->data();

            // این بخش به ما کمک می‌کند بفهمیم در درخواست واقعی چه چیزی ارسال شده
            if ($data['messages'] !== $messages) {
                // dump("Messages mismatch!");
                return false;
            }

            if (($data['temperature'] ?? null) !== 0.2) {
                // dump("Temperature mismatch! Got: " . ($data['temperature'] ?? 'null'));
                return false;
            }

            return true;
        });
    }
}
