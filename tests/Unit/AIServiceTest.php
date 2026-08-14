<?php

namespace Tests\Unit;

use App\DTO\ChatResponseDTO;
use Tests\TestCase;
use Tests\Unit;
use App\Services\AIService;
use App\Services\AIClientInterface;
use Generator;
use Mockery;
use RuntimeException;
use App\AI\Prompts\Templates\DocumentAnalysisPrompt;
use App\AI\Prompts\PromptRenderer;

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

            $this->assertSame(4, $fakeClient->callCount);
        }
    }


    public function test_structured_returns_data_from_native_openai_response(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
            ],
            'required' => ['summary', 'priority'],
        ];

        $jsonResponse = json_encode([
            'summary' => 'Payment issue detected',
            'priority' => 'high',
        ]);

        $client = $this->createMock(AIClientInterface::class);

        //We expect chat to be called only once and it must include response_format.
        $client->expects($this->once())
            ->method('chat')
            ->with(
                $this->isArray(),
                $this->callback(function (array $options) use ($schema) {
                    return isset($options['response_format']['type'])
                        && $options['response_format']['type'] === 'json_schema'
                        && ($options['response_format']['json_schema']['schema'] ?? null) === $schema
                        && ($options['temperature'] ?? null) === 0;
                })
            )->willReturn([
                'success' => true,
                'data' => [
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => $jsonResponse,
                            ],
                        ],
                    ],
                ],
            ]);

        $service = new AIService($client);

        $result = $service->structured('Analyze this ticket', $schema);

        $this->assertSame(['summary' => 'Payment issue detected', 'priority' => 'high'], $result);
    }


    public function test_structured_falls_back_when_native_path_fails(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
            ],
            'required' => ['summary', 'priority'],
        ];

        $fallbackJson = json_encode([
            'summary' => 'Server outage confirmed',
            'priority' => 'urgent',
        ]);

        $client = $this->createMock(AIClientInterface::class);

        $client->expects($this->exactly(2))
            ->method('chat')
            ->willReturnCallback(function (array $messages, array $options = []) use ($schema, $fallbackJson) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    if (
                        !isset($options['response_format']['type']) ||
                        $options['response_format']['type'] !== 'json_schema' ||
                        ($options['response_format']['json_schema']['schema'] ?? null)
                    ) {
                        $this->fail('First call must use native json_schema response_format.');
                    }

                    throw new RuntimeException('OpenAI native structured output failed.');
                }

                if (isset($options['response_format'])) {
                    $this->fail('Fallback call must not include response_format.');
                }

                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => $fallbackJson,
                                ],
                            ],
                        ],
                    ],
                ];
            });

        $service = new AIService($client);

        $result = $service->structured('Analyze this outage report', $schema);

        $this->assertSame(['summary' => 'Server outage confirmed', 'priority' => 'urgent'], $result);
    }


    public function test_structured_throws_when_native_and_all_fallback_attempts_fail(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
                'priority' => ['type' => 'string'],
            ],
            'required' => ['summary', 'priority'],
        ];

        $client = $this->createMock(AIClientInterface::class);

        $client->expects($this->exactly(4))
            ->method('chat')
            ->willReturnCallback(function (array $messages, array $options = []) use ($schema) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    if (
                        !isset($options['response_format']['type']) ||
                        $options['response_format']['type'] !== 'json_schema' ||
                        ($options['response_format']['json_schema']['schema'] ?? null)
                    ) {
                        $this->fail('First call must use native json_schema response_format.');
                    }

                    throw new RuntimeException('Native structured output failed.');
                }

                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => 'not-a-valid-json-response',
                                ],
                            ],
                        ],
                    ],
                ];
            });

        $service = new AIService($client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI failed to return a valid JSON string.');

        $service->structured('Analyze this broken input', $schema);
    }


    public function test_execute_prompt_renders_and_executes_a_structured_template(): void
    {
        $document = 'The payment gateway returned an error for transaction #1234.';


        $expectedResponse =
            [
                'summary' => 'A payment gateway error occurred for transaction #1234.',
                'category' => 'payment',
            ];

        $client = $this->createMock(AIClientInterface::class);

        $client->expects($this->once())
            ->method('chat')
            ->with(
                $this->callback(function (array $messages) use ($document): bool {
                    $systemMessage = $messages[0]['content'] ?? '';
                    $userMessage = $messages[1]['content'] ?? '';

                    return str_contains($systemMessage, 'expert document analyst')
                        && str_contains($userMessage, $document);
                }),
                $this->callback(function (array $options): bool {
                    return ($options['response_format']['type'] ?? null) === 'json_schema'
                        && ($options['temperature'] ?? null) === 0;
                })
            )->willReturn(
                [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => json_encode($expectedResponse),
                                ],
                            ],
                        ],
                    ],
                ],
            );

        $service = new AIService($client, new PromptRenderer());

        $result = $service->executePrompt(
            new DocumentAnalysisPrompt(),
            ['document' => $document]
        );

        $this->assertSame($expectedResponse, $result);
    }
}
