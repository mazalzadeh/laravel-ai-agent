<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Prompts;

use App\AI\Prompts\AbstractPrompt;
use App\AI\Prompts\Contracts\PromptTemplate;
use App\AI\Prompts\ContextAwarePromptExecutor;
use App\AI\Prompts\PromptExecutionResult;
use App\AI\Prompts\PromptRenderer;
use App\Services\AIService;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Mockery;

class ContextAwarePromptExecutorTest extends TestCase
{
    protected PromptRenderer $renderer;
    protected AIService $aiservice;
    protected ContextAwarePromptExecutor $executor;


    protected function setUp(): void
    {
        $this->renderer = new PromptRenderer();
        $this->aiservice = Mockery::mock(AIService::class);

        $this->executor = new ContextAwarePromptExecutor(
            renderer: $this->renderer,
            aiservice: $this->aiservice
        );
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    /**
     * Helper to create a test prompt implementation.
     */
    protected function makeTestPrompt(
        string $system = 'Context: {{context}}',
        string $user = 'Question: {{question}}'
    ): PromptTemplate {
        return new class($system, $user) implements PromptTemplate {
            public function __construct(
                private readonly string $system,
                private readonly string $user
            ) {}

            public function getSystemMessage(): string
            {
                return $this->system;
            }

            public function getUserMessage(): string
            {
                return $this->user;
            }

            public function getSchema(): ?array
            {
                return null;
            }
        };
    }


    public function test_it_executes_prompt_with_context_and_returns_result(): void
    {
        $prompt = $this->makeTestPrompt(
        system: 'Context: {{context}}',
        user: 'Question: {{question}}'
    );
    $context = 'Laravel is a web application framework with expressive syntax.';
    $query = 'What is Laravel?';
    $aiResponse = 'Laravel is a modern PHP framework.';

    $expectedMessages = [
        ['role' => 'system', 'content' => 'Context: ' . $context],
        ['role' => 'user', 'content' => 'Question: ' . $query],
    ];

    $this->aiservice->shouldReceive('chat')
        ->once()
        ->with($expectedMessages)
        ->andReturn($aiResponse);

    $result = $this->executor->execute(
        prompt: $prompt,
        context: $context,
        query: $query
    );

    $this->assertInstanceOf(PromptExecutionResult::class, $result);
    $this->assertSame($aiResponse, $result->content);
    $this->assertFalse($result->isEmpty());
    $this->assertTrue($result->hasContext());
    }


    public function test_it_applies_fallback_when_context_is_empty(): void
    {
        $prompt = $this->makeTestPrompt(
        system: 'Context: {{context}}',
        user: 'Question: {{question}}'
    );
    $emptyContext = '   ';
    $query = 'Tell me about quantum computing.';
    $fallback = 'No relevant context found.';
    $aiResponse = 'I do not have enough context.';

    $expectedMessages = [
        ['role' => 'system', 'content' => 'Context: ' . $fallback],
        ['role' => 'user', 'content' => 'Question: ' . $query],
    ];

    $this->aiservice->shouldReceive('chat')
        ->once()
        ->with($expectedMessages)
        ->andReturn($aiResponse);

    $result = $this->executor->execute(
        prompt: $prompt,
        context: $emptyContext,
        query: $query,
        fallbackContext: $fallback
    );

    $this->assertInstanceOf(PromptExecutionResult::class, $result);
    $this->assertSame($aiResponse, $result->content);
    $this->assertFalse($result->hasContext());
    }
}
