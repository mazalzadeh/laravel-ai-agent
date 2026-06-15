<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Services\AIClientInterface;
use Nette\Schema\Elements\Structure;
use App\DTO\ChatResponseDTO;

class ChatEndpointTest extends TestCase
{
    /*public function test_chat_endpoint_returns_reply()
    {
        $fakeClient = new class implements AIClientInterface{
            public function chat(string $message) : array
            {
                return[
                    'id' => 'chatcmpl-test',
                    'choices' => [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'You said: '. $message
                        ]
                    ]
                ];
            }
        };

        // $this->app->instance(AIClientInterface::class, $fakeClient);
        $this->app->bind(AIClientInterface::class, fn()=>$fakeClient);

        $response = $this->postJson('/api/chat', ['message' => 'Hello']);

        $response->assertStatus(200)->assertJson(['reply' => 'You said: Hello']);
    }*/

    public function test_chat_endpoint_returns_reply()
    {
        $fakeClient = new class implements AIClientInterface {

            public function chat(array $messages, array $options = []): array
            {
                $userMessage = $messages[0]['content'] ?? '';
                return [
                    'success' => true,
                    'data' => new ChatResponseDTO(
                        id: 'chatcmpl-test',
                        content: 'You said: ' . $userMessage,
                        role: 'assistant'
                    )
                ];
            }

            public function streamChat(array $messages, array $options = []): \Generator
            {
                yield from [];
            }

            public function embed(string $text): array
            {
                return [0.1, 0.2, 0.3];
            }
        };

        $this->app->bind(AIClientInterface::class, fn() => $fakeClient);

        $response = $this->postJson('/api/chat', [
            'message' => 'Hello'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'reply' => 'You said: Hello'
            ]);
    }
}
