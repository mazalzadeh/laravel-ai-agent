<?php

namespace Tests\Feature;

use App\Agent\Context\StepContext;
use App\Agent\Enums\AgentAction;
use App\Agent\Services\AgentOrchestrator;
use App\Services\AIClientInterface;
use Generator;
use Tests\TestCase;

class AgentEndToEndTest extends TestCase
{
    public function test_agent_executes_direct_answer_pipeline_and_records_steps(): void
    {
        $expectedAnswer = 'Final E2E test answer';
        $userInput = 'A test question';

        $fakeClient = new class($expectedAnswer) implements AIClientInterface {
            /**
             * Initialize the test client with its expected answer.
             */
            public function __construct(private string $answer) {}

            public function chat(array $messages, array $options = []): array
            {
                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => json_encode([
                                        'action' => AgentAction::DIRECT_ANSWER->value,
                                        'parameters' => ['text' => $this->answer],
                                        'reasoning' => 'Direct answer is sufficient.',
                                    ], JSON_THROW_ON_ERROR)
                                ],
                            ],
                        ],
                    ],
                ];
            }

            /**
             * Yield a test stream chunk.
             *
             * @param array $messages The prompt messages.
             * @param array $options Optional streaming settings.
             * @return Generator The stream of response chunks.
             */
            public function streamChat(array $messages, array $options = []): Generator
            {
                yield 'test';
            }


            public function embed(string $text): array
            {
                return [];
            }
        };

        $this->app->instance(AIClientInterface::class, $fakeClient);

        $orchestrator = $this->app->make(AgentOrchestrator::class);
        $stepContext = new StepContext();

        $response = $orchestrator->runWithStepContext($userInput, $stepContext);

        $this->assertSame($expectedAnswer, $response);

        $steps = $stepContext->toArray()['steps'];

        $this->assertCount(2, $steps);

        $this->assertSame('decision', $steps[0]['name']);
        $this->assertSame($userInput, $steps[0]['input']);
        $this->assertSame(AgentAction::DIRECT_ANSWER->value, $steps[0]['output']);
        $this->assertSame(
            AgentAction::DIRECT_ANSWER->value,
            $steps[0]['decision']['action']
        );
        $this->assertSame('final_answer', $steps[1]['name']);
        $this->assertSame($userInput, $steps[1]['input']);
        $this->assertSame($expectedAnswer, $steps[1]['output']);
    }


    public function test_agent_falls_back_to_direct_answer_when_ai_returns_malformed_json(): void
    {
        $malformedContent = 'This is an unformatted error response or raw text from LLM.';
        $userInput = 'Can you help me?';

        $failingClient = new class($malformedContent) implements AIClientInterface {
            /**
             * Initialize the test client with malformed content.
             */
            public function __construct(private string $rawContent) {}

            /**
             * Return a raw non-JSON string in the response envelope.
             *
             * @param array $messages The prompt messages.
             * @param array $options Optional generation settings.
             * @return array The fake response payload.
             */
            public function chat(array $messages, array $options = []): array
            {
                return [
                    'success' => true,
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'role' => 'assistant',
                                    'content' => $this->rawContent,
                                ],
                            ],
                        ],
                    ],
                ];
            }

            /**
             * Yield a test stream chunk.
             *
             * @param array $messages The prompt messages.
             * @param array $options Optional streaming settings.
             * @return Generator The stream of response chunks.
             */
            public function streamChat(array $messages, array $options = []): Generator
            {
                yield 'test';
            }

            /**
             * Return an empty embedding vector.
             *
             * @param string $text The text to embed.
             * @return array The embedding vector.
             */
            public function embed(string $text): array
            {
                return [];
            }
        };

        // Bind the failing client into the container
        $this->app->instance(AIClientInterface::class, $failingClient);

        $orchestrator = $this->app->make(AgentOrchestrator::class);
        $stepContext = new StepContext();

        $response = $orchestrator->runWithStepContext($userInput, $stepContext);

        // Verify that the fallback mechanism returned the raw content safely
        $this->assertSame($malformedContent, $response);

        // Verify that steps were still recorded accurately
        $steps = $stepContext->toArray()['steps'];
        $this->assertCount(2, $steps);

        // Decision step assertions
        $this->assertSame('decision', $steps[0]['name']);
        $this->assertSame(AgentAction::DIRECT_ANSWER->value, $steps[0]['output']);
        $this->assertSame(
            AgentAction::DIRECT_ANSWER->value,
            $steps[0]['decision']['action']
        );
        $this->assertSame($malformedContent, $steps[0]['decision']['parameters']['text']);

        // Final answer step assertions
        $this->assertSame('final_answer', $steps[1]['name']);
        $this->assertSame($malformedContent, $steps[1]['output']);
    }
}
