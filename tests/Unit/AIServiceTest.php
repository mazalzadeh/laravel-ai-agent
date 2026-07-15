<?php

namespace Tests\Unit;

use App\DTO\ChatResponseDTO;
use PHPUnit\Framework\TestCase;
use Tests\Unit;
use App\Services\AIService;
use App\Services\AIClientInterface;
use Generator;
use GrahamCampbell\ResultType\Success;
use Illuminate\Support\Facades\Http;
use Mockery;
use PhpParser\Node\Expr\FuncCall;
use RuntimeException;

class AIServiceTest extends TestCase
{
    public function test_chat_returns_ai_message()
    {
        $fakeClient = new class implements AIClientInterface {
            public function chat(array $messages, array $options = []): array
            {
                /*return [
                    'success' => true,
                    'data' => new ChatResponseDTO(
                        id: 'chatcmpl-test',
                        content: 'You said: ' . $messages[0]['content'],
                        role: 'assistant'
                    )
                ];*/
                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => 'You said: ' . $messages[0]['content'],
                                ]
                            ]
                        ]
                    ]
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

        $service = new AIService($fakeClient);

        $reply = $service->chat([['role' => 'user', 'content' => 'Hello']]);

        $this->assertEquals('You said: Hello', $reply);
    }

    public function test_chat_returns_default_when_response_invalid()
    {
        $fakeClient = new class implements AIClientInterface {

            public function chat(array $messages, array $options = []): array
            {
                /*return [
                    'success' => true,
                    'data' => new ChatResponseDTO(
                        id: 'chatcmpl-test',
                        content: '',
                        role: 'assistant'
                    )
                ];*/
                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => '',
                                ]
                            ]
                        ]
                    ]
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

        $service = new AIService($fakeClient);

        // $reply = $service->chat(['role' => 'user', 'content' => 'Hello']);

        $reply = $service->chat([['role' => 'user', 'content' => 'Hello']]);

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
                /*return [
                    'success' => true,
                    'data' => ChatResponseDTO::fromArray([
                        'id' => 'chatcmpl_123',
                        'choices' => [
                            [
                                'message' => ['role' => 'assistant', 'content' => $jsonResponse]
                            ]
                        ]
                    ])
                ];*/
                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => $jsonResponse
                                ]
                            ]
                        ]
                    ]
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

        $service = new AIService($fakeClient);

        $result = $service->analyzeText('The server will be down for 2 hours.');

        $this->assertIsArray($result);
        $this->assertSame('Server Maintenance', $result['subject']);
        $this->assertSame('High', $result['priority']);
    }

    public function test_it_calls_client_stream_chat_and_returns_generator(): void
    {
        $messages = [['role' => 'user', 'content' => 'Hello']];

        $options = ['temperature' => 0.7];

        $generatorFactory = function (): Generator {
            yield 'Hello';
            yield 'world';
        };

        $expectedGenerator = $generatorFactory();

        $client = Mockery::mock(AIClientInterface::class);
        $client->shouldReceive('streamChat')
            ->once()
            ->with($messages, $options)
            ->andReturn($expectedGenerator);

        $service = new AIService($client);

        $result = $service->streamChat($messages, $options);

        $this->assertInstanceOf(Generator::class, $result);
        $this->assertSame(['Hello', 'world'], iterator_to_array($result));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_structured_retries_when_first_response_is_invalid_json(): void
    {
        $fakeClient = new class implements AIClientInterface {
            public int $callCount = 0;

            public function chat(array $messages, array $options = []): array
            {
                $this->callCount++;

                $content =
                    $this->callCount === 1
                    ? 'invalid json'
                    : json_encode(['subject' => 'Server Maintenance', 'priority' => 'High']);

                return [
                    'success' => true,
                    'data' => ['choices' => [['message' => ['role' => 'assistant', 'content' => $content,],],],]
                ];
            }

            public function streamChat(array $messages, array $options = []): \Generator
            {
                yield from [];
            }

            public function embed(string $text): array
            {
                return [];
            }
        };

        $schema = [
            'type' => 'object',
            'required' => ['subject', 'priority'],
            'properties' => [
                'subject' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
            ],
        ];

        $service = new AIService($fakeClient);

        $result = $service->structured('Analyze the server maintenance message.', $schema);

        $this->assertSame('Server Maintenance', $result['subject']);
        $this->assertSame('High', $result['priority']);
        $this->assertSame(2, $fakeClient->callCount);
    }


    public function test_structured_retries_when_first_response_fails_schema_validation(): void
    {
        $fakeClient = new class implements AIClientInterface {
            public int $callCount = 0;

            public function chat(array $messages, array $options = []): array
            {
                $this->callCount++;

                $content = $this->callCount === 1
                    ? json_encode(['subject' => 'Server Maintenance',])
                    : json_encode(['subject' => 'Server Maintenance', 'priority' => 'High']);

                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => $content,
                                ],
                            ],
                        ],
                    ],
                ];
            }

            public function streamChat(array $messages, array $options = []): \Generator
            {
                yield from [];
            }

            public function embed(string $text): array
            {
                return [];
            }
        };

        $schema = [
            'type' => 'object',
            'required' => ['subject', 'priority'],
            'properties' => [
                'subject' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
            ],
        ];

        $service = new AIService($fakeClient);

        $result = $service->structured('Analyze the server maintenance message.', $schema);

        $this->assertSame('Server Maintenance', $result['subject']);
        $this->assertSame('High', $result['priority']);
        $this->assertSame(2, $fakeClient->callCount);
    }


    public function test_structured_throws_exception_after_three_invalid_responses(): void
    {
        $fakeClient = new class implements AIClientInterface {
            public int $callCount = 0;

            public function chat(array $messages, array $options = []): array
            {
                $this->callCount++;

                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => 'invalid json'
                                ],
                            ],
                        ],
                    ],
                ];
            }


            public function streamChat(array $messages, array $options = []): \Generator
            {
                yield from [];
            }

            public function embed(string $text): array
            {
                return [];
            }
        };

        $schema = [
            'type' => 'object',
            'required' => ['subject', 'priority'],
            'properties' => [
                'subject' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
            ],
        ];

        $service = new AIService($fakeClient);

        try {
            $service->structured('Analyze the server maintenance message.', $schema);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'AI failed to return a valid JSON string.',
                $exception->getMessage()
            );

            $this->assertSame(3, $fakeClient->callCount);
        }
    }
}
