<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Services\Llm\LlmClient;
use App\Services\Llm\ConversationRunner;
use App\AI\ToolRegistry;
use App\AI\ToolExecutor;
use App\AI\Tools\GetOrderStatusTool;
use App\AI\Tools\ToolInterface;
use Tests\TestCase;

class FunctionCallingTest extends TestCase
{
    public function test_can_process_complete_function_calling_flow_with_fake_client(): void
    {
        // Arrange
        $registry = new ToolRegistry();
        $registry->register(new GetOrderStatusTool());

        $executor = new ToolExecutor($registry);
        $client = new LlmClient();

        $runner = new ConversationRunner(
            $client,
            $registry,
            $executor
        );

        // Act
        $userMessage = 'وضعیت سفارش ORD-9988 را بگو';
        $finalAnswer = $runner->run($userMessage);
        $history = $runner->getHistory();

        // Assert final answer
        $this->assertStringContainsString('shipped', $finalAnswer);
        $this->assertStringContainsString('Post Iran', $finalAnswer);
        $this->assertStringContainsString('IR9876543210', $finalAnswer);
        $this->assertStringContainsString('2026-08-02', $finalAnswer);
        $this->assertStringContainsString('paid', $finalAnswer);

        // The expected conversation flow:
        // 0: user message
        // 1: assistant tool-call request
        // 2: tool execution result
        // 3: assistant final response
        $this->assertCount(4, $history);

        // User message
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame($userMessage, $history[0]['content']);

        // Assistant tool call
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertArrayHasKey('tool_calls', $history[1]);
        $this->assertNotEmpty($history[1]['tool_calls']);
        $this->assertSame(
            'get_order_status',
            $history[1]['tool_calls'][0]['function']['name']
        );

        // Tool result
        $this->assertSame('tool', $history[2]['role']);
        $this->assertSame('get_order_status', $history[2]['name']);
        $this->assertArrayHasKey('tool_call_id', $history[2]);
        $this->assertSame(
            $history[1]['tool_calls'][0]['id'],
            $history[2]['tool_call_id']
        );
        $this->assertStringContainsString(
            'IR9876543210',
            $history[2]['content']
        );

        // Final assistant response
        $this->assertSame('assistant', $history[3]['role']);
        $this->assertSame($finalAnswer, $history[3]['content']);
        $this->assertArrayNotHasKey('tool_calls', $history[3]);
    }


    public function test_it_returns_structured_tool_error_for_invalid_json_arguments(): void
    {
        //Arrange
        $registry = new ToolRegistry();
        $registry->register(new GetOrderStatusTool());

        $executor = new ToolExecutor($registry);

        $client = new class extends LlmClient {

            protected function simulateToolCallResponse(string $userPrompt): array
            {
                return [
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => null,
                                'tool_calls' => [
                                    [
                                        'id' => 'call_invalid_json_1',
                                        'type' => 'function',
                                        'function' => [
                                            'name' => 'get_order_status',
                                            'arguments' => '{"order_id":"ORD-9988'
                                        ],
                                    ],
                                ],
                            ],
                            'finish_reason' => 'tool_calls',
                        ],
                    ],
                ];
            }
        };

        $runner = new ConversationRunner(
            $client,
            $registry,
            $executor
        );

        //Act
        $finalAnswer = $runner->run('وضعیت سفارش ORD-9988 را بگو');
        $history = $runner->getHistory();

        //Assert conversation flow
        $this->assertCount(4, $history);

        //User message
        $this->assertSame('user', $history[0]['role']);

        //Assistant tool call
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertArrayHasKey('tool_calls', $history[1]);
        $this->assertSame(
            'get_order_status',
            $history[1]['tool_calls'][0]['function']['name']
        );

        //Tool error message
        $this->assertSame('tool', $history[2]['role']);
        $this->assertSame('call_invalid_json_1', $history[2]['tool_call_id']);
        $this->assertSame('get_order_status', $history[2]['name']);

        $toolError = json_decode($history[2]['content'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($toolError['success']);
        $this->assertSame('invalid_json', $toolError['error']['type']);
        $this->assertArrayHasKey('message', $toolError['error']);

        //Final assistant response
        $this->assertSame('assistant', $history[3]['role']);
        $this->assertSame($finalAnswer, $history[3]['content']);
        $this->assertArrayNotHasKey('tool_calls', $history[3]);
    }


    public function test_it_returns_structured_tool_error_for_invalid_tool_arguments(): void
    {
        //Arrange
        $registry = new ToolRegistry();
        $registry->register(new GetOrderStatusTool());

        $executor = new ToolExecutor($registry);

        $client = new class extends LlmClient {
            protected function simulateToolCallResponse(string $userPrompt): array
            {
                return [
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => null,
                                'tool_calls' =>
                                [
                                    [
                                        'id' => 'call_invalid_arguments_1',
                                        'type' => 'function',
                                        'function' => [
                                            'name' => 'get_order_status',
                                            'arguments' => json_encode([
                                                'order_id' => 'INVALID',
                                            ]),
                                        ],
                                    ],
                                ],
                            ],
                            'finish_reason' => 'tool_calls',
                        ],
                    ],
                ];
            }
        };

        $runner = new ConversationRunner($client, $registry, $executor);

        //Act
        $finalAnswer = $runner->run('وضعیت سفارش INVALID را بگو');
        $history = $runner->getHistory();

        //Assert conversation flow
        $this->assertCount(4, $history);

        $this->assertSame('user', $history[0]['role']);

        $this->assertSame('assistant', $history[1]['role']);
        $this->assertArrayHasKey('tool_calls', $history[1]);
        $this->assertSame(
            'get_order_status',
            $history[1]['tool_calls'][0]['function']['name']
        );

        $this->assertSame('tool', $history[2]['role']);
        $this->assertSame('call_invalid_arguments_1', $history[2]['tool_call_id']);
        $this->assertSame('get_order_status', $history[2]['name']);

        $toolError = json_decode(
            $history[2]['content'],
            true,
            512,

            JSON_THROW_ON_ERROR
        );

        $this->assertFalse($toolError['success']);
        $this->assertSame('validation_error', $toolError['error']['type']);
        $this->assertArrayHasKey('message', $toolError['error']);
        $this->assertArrayHasKey('details', $toolError['error']);

        $this->assertArrayHasKey('errors', $toolError['error']['details']);
        $this->assertArrayHasKey('order_id', $toolError['error']['details']['errors']);

        $this->assertSame('assistant', $history[3]['role']);
        $this->assertSame($finalAnswer, $history[3]['content']);
        $this->assertArrayNotHasKey('tool_calls', $history[3]);
    }


    public function test_it_returns_structured_tool_error_for_unknown_tool(): void
    {
        //Arrange
        $registry = new ToolRegistry();
        $registry->register(new GetOrderStatusTool());

        $executor = new ToolExecutor($registry);

        $client = new class extends LlmClient {
            protected function simulateToolCallResponse(string $userPrompt): array
            {
                return [
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => null,
                                'tool_calls' => [
                                    [
                                        'id' => 'call_unknown_tool_1',
                                        'type' => 'function',
                                        'function' => [
                                            'name' => 'unknown_tool',
                                            'arguments' => '{}',
                                        ],
                                    ],
                                ],
                            ],
                            'finish_reason' => 'tool_calls',
                        ],
                    ],
                ];
            }
        };

        $runner = new ConversationRunner($client, $registry, $executor);

        //Act
        $userMessage = 'ابزار ناشناخته را اجرا کن';
        $finalAnswer = $runner->run($userMessage);
        $history = $runner->getHistory();

        //Assert conversation flow
        $this->assertCount(4, $history);

        //User message
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame($userMessage, $history[0]['content']);

        //Assistant tool call
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertArrayHasKey('tool_calls', $history[1]);

        $this->assertSame('call_unknown_tool_1', $history[1]['tool_calls'][0]['id']);

        $this->assertSame('unknown_tool', $history[1]['tool_calls'][0]['function']['name']);

        //Structured tool error
        $this->assertSame('tool', $history[2]['role']);
        $this->assertSame('call_unknown_tool_1', $history[2]['tool_call_id']);
        $this->assertSame('unknown_tool', $history[2]['name']);

        $toolError = json_decode($history[2]['content'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($toolError['success']);

        $this->assertSame('tool_execution_error', $toolError['error']['type']);

        $this->assertArrayHasKey('message', $toolError['error']);

        $this->assertStringContainsString('unknown_tool', $toolError['error']['message']);

        //Final assistant response
        $this->assertSame('assistant', $history[3]['role']);
        $this->assertSame($finalAnswer, $history[3]['content']);
        $this->assertArrayNotHasKey('tool_calls', $history[3]);

        //simulateFinalResponse() user "unkown" for missing order fields.
        $this->assertStringContainsString('unknown', $finalAnswer);
    }


    public function test_it_returns_structured_tool_error_for_unexpected_exception(): void
    {
        //Arrange
        $registry = new ToolRegistry();

        $failingTool = new class implements ToolInterface {
            public function name(): string
            {
                return 'failing_tool';
            }

            public function description(): string
            {
                return 'A test tool that always throws an exception.';
            }

            public function parameters(): array
            {
                return [
                    'type' => 'object',
                    'properties' => [],
                    'required' => [],
                    'additionalProperties' => false,
                ];
            }

            public function execute(array $arguments): array
            {
                throw new \RuntimeException(
                    'Unexpected failure while executing the tool.'
                );
            }
        };

        $registry->register($failingTool);

        $executor = new ToolExecutor($registry);

        $client = new class extends LlmClient {
            protected function simulateToolCallResponse(string $userPrompt): array
            {
                return [
                    'choices' => [
                        [
                            'message' => [
                                'role' => 'assistant',
                                'content' => null,
                                'tool_calls' => [
                                    [
                                        'id' => 'call_failing_tool_1',
                                        'type' => 'function',
                                        'function' => [
                                            'name' => 'failing_tool',
                                            'arguments' => '{}',
                                        ],
                                    ],
                                ],
                            ],
                            'finish_reason' => 'tool_calls',
                        ],
                    ],
                ];
            }
        };

        $runner = new ConversationRunner($client, $registry, $executor);

        //Act
        $userMessage = 'ابزار آزمایشی را اجرا کن';
        $finalAnswer = $runner->run($userMessage);
        $history = $runner->getHistory();

        //Assert complete conversation flow
        $this->assertCount(4, $history);

        //0: User message
        $this->assertSame('user', $history[0]['role']);
        $this->assertSame($userMessage, $history[0]['content']);

        //1: Assistant request the failing tool
        $this->assertSame('assistant', $history[1]['role']);
        $this->assertArrayHasKey('tool_calls', $history[1]);

        $this->assertSame('call_failing_tool_1', $history[1]['tool_calls'][0]['id']);

        $this->assertSame('failing_tool', $history[1]['tool_calls'][0]['function']['name']);

        //2: Structured tool execution error
        $this->assertSame('tool', $history[2]['role']);
        $this->assertSame('call_failing_tool_1', $history[2]['tool_call_id']);
        $this->assertSame('failing_tool', $history[2]['name']);

        $toolError = json_decode($history[2]['content'], true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($toolError['success']);

        $this->assertSame('tool_execution_error', $toolError['error']['type']);

        $this->assertArrayHasKey('message', $toolError['error']);

        $this->assertStringContainsString(
            'Unexpected failure while executing the tool.',
            $toolError['error']['message']
        );

        //3: Final assistant response
        $this->assertSame('assistant', $history[3]['role']);
        $this->assertSame($finalAnswer, $history[3]['content']);
        $this->assertArrayNotHasKey('tool_calls', $history[3]);

        // current Fake client uses "unknown" for missing order fields.
        $this->assertStringContainsString('tool_execution_error', $finalAnswer);
        $this->assertStringContainsString('Unexpected failure', $finalAnswer);
    }


    public function test_it_stops_when_conversation_exceeds_maximum_iterations(): void
{
    $client = new class extends LlmClient {
        public int $chatCalls = 0;

        public function chat(array $messages, array $tools = []): array
        {
            $this->chatCalls++;

            return [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call_loop_' . $this->chatCalls,
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'get_order_status',
                                        'arguments' => json_encode([
                                            'order_id' => 'ORD-9988',
                                        ]),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
    };

    $registry = new ToolRegistry();
    $registry->register(new GetOrderStatusTool());

    $executor = new ToolExecutor($registry);

    $runner = new ConversationRunner(
        client: $client,
        registry: $registry,
        executor: $executor,
        maxIterations: 3,
    );

    try {
        $runner->run('وضعیت سفارش ORD-9988 را بگو');
        $this->fail('Expected the conversation loop limit exception to be thrown.');
    } catch (\RuntimeException $exception) {
        $this->assertSame(
            'Conversation loop exceeded maximum allowed iterations (3).',
            $exception->getMessage()
        );
    }

    $this->assertSame(3, $client->chatCalls);
}

}
