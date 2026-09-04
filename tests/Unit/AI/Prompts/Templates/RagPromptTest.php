<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Prompts\Templates;

use App\AI\Prompts\Contracts\PromptTemplate;
use App\AI\Prompts\PromptRenderer;
use App\AI\Prompts\Templates\RagPrompt;
use PHPUnit\Framework\TestCase;

final class RagPromptTest extends TestCase
{
    private RagPrompt $prompt;
    private PromptRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prompt = new RagPrompt();
        $this->renderer = new PromptRenderer();
    }


    public function test_it_implements_prompt_template_interface(): void
    {
        $this->assertInstanceOf(PromptTemplate::class, $this->prompt);
    }


    public function test_it_provides_system_message_with_rules(): void
    {
        $systemMessage = $this->prompt->getSystemMessage();

        $this->assertNotEmpty($systemMessage);
        $this->assertStringContainsString('context', strtolower($systemMessage));
        $this->assertStringContainsString('cite', strtolower($systemMessage));
        $this->assertStringContainsString('language', strtolower($systemMessage));
    }


    public function test_it_contains_context_and_question_placeholders(): void
    {
        $userMessage = $this->prompt->getUserMessage();

        $this->assertStringContainsString('{{context}}', $userMessage);
        $this->assertStringContainsString('{{question}}', $userMessage);
    }


    public function test_it_renders_successfully_with_context_and_question(): void
    {
        $messages = $this->renderer->render($this->prompt, [
            'context' => 'Laravel 12 was released with new concurrency features.',
            'question' => 'What is new in Laravel 12?',
        ]);

        $this->assertCount(2, $messages);

        $this->assertSame('system', $messages[0]['role']);
        $this->assertSame($this->prompt->getSystemMessage(), $messages[0]['content']);

        $this->assertSame('user', $messages[1]['role']);
        $this->assertStringContainsString('Laravel 12 was released with new concurrency features.', $messages[1]['content']);
        $this->assertStringContainsString('What is new in Laravel 12?', $messages[1]['content']);
        $this->assertStringNotContainsString('{{context}}', $messages[1]['content']);
        $this->assertStringNotContainsString('{{question}}', $messages[1]['content']);
    }


    public function test_it_returns_null_schema_for_free_text_chat(): void
    {
        $this->assertNull($this->prompt->getSchema());
    }
}
