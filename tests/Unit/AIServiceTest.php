<?php

namespace Tests\Unit;

use App\DTO\ChatResponseDTO;
use PHPUnit\Framework\TestCase;
use Tests\Unit;
use App\Services\AIService;
use App\Services\AIClientInterface;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Http;

class AIServiceTest extends TestCase
{
    public function test_chat_returns_ai_message()
    {
        $fakeClient = new class implements AIClientInterface{
            public function chat(array $messages, array $options = []): array
            {
                return[
                    'success' => true,
                    'data' => new ChatResponseDTO(
                        id: 'chatcmpl-test',
                        content: 'You said: ' . $messages[0]['content'],
                        role: 'assistant'
                    )
                ];
            }
        };

        $service = new AIService($fakeClient);

        $reply = $service->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertEquals('You said: Hello', $reply);
    }

    public function test_chat_returns_default_when_response_invalid()
    {
        $fakeClient = new class implements AIClientInterface {

            public function chat(array $messages, array $options = []): array
            {
                return [
                    'success' => true,
                    'data' => new ChatResponseDTO(
                        id: 'chatcmpl-test',
                        content: '',
                        role: 'assistant'
                    )
                ];
            }
        };

        $service = new AIService($fakeClient);

        $reply = $service->chat(['role' => 'user', 'content' => 'Hello']);

        $this->assertEquals('No response content from AI.', $reply);
    }

    public function test_analyze_text_returns_structured_json()
    {
        $fakeClient = new class implements AIClientInterface {
            public function chat(array $messages, array $options = []): array
            {
                if (($options['response_format']['type'] ?? null) !== 'json_object') {
                    return ['success' => false];
                }
                $jsonResponse = json_encode([
                    'subject' => 'Server Maintenance',
                    'priority' => 'High',
                    'summary' => 'The server will be down for 2 hours.'
                ]);
                return [
                    'success' => true,
                    'data' => ChatResponseDTO::fromArray([
                        'id' => 'chatcmpl_123',
                        'choices' => [
                            [
                                'message' => ['role' => 'assistant', 'content' => $jsonResponse]
                            ]
                        ]
                    ])
                ];
            }
        };

        $service = new AIService($fakeClient);

        $result = $service->analyzeText('The server will be down for 2 hours.');

        $this->assertIsArray($result);
        $this->assertSame('Server Maintenance', $result['subject']);
        $this->assertSame('High', $result['priority']);
    }
}
