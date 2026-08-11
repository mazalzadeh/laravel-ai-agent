<?php

declare(strict_type=1);

namespace Tests\Unit\AI\Prompts;

use App\AI\Prompts\Contracts\PromptTemplate;
use App\AI\Prompts\PromptRenderer;
use PHPUnit\Framework\TestCase;

class PromptRendererTest extends TestCase
{
    public function test_it_renders_system_and_user_message_with_variable(): void
    {
        $renderer = new PromptRenderer();

        $template = new class implements PromptTemplate {
            public function getSystemMessage(): string
            {
                return 'You are a helpful assistant.';
            }

            public function getUserMessage(): string
            {
                return 'Analyze this text: {{text}}';
            }

            public function getSchema(): ?array
            {
                return null;
            }
        };

        $messages = $renderer->render($template, ['text' => 'Hello World']);

        $this->assertSame([
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant.',
            ],
            [
                'role' => 'user',
                'content' => 'Analyze this text: Hello World',
            ]
        ], $messages);
    }



    public function test_it_keeps_unknown_placeholders_unchanged(): void
    {
        $renderer = new PromptRenderer();

        $template = new class implements PromptTemplate {
            public function getSystemMessage(): string
            {
                return 'System message.';
            }

            public function getUserMessage(): string
            {
                return 'Hello {{name}} from {{city}}';
            }

            public function getSchema(): ?array
            {
                return null;
            }
        };

        $messages = $renderer->render($template, ['name' => 'Ali']);

        $this->assertSame('Hello Ali from {{city}}', $messages[1]['content']);
    }


    public function test_it_replaces_a_repeated_placeholder_in_all_positions(): void
    {
        $renderer = new PromptRenderer();

        $template = new class implements PromptTemplate {
            public function getSystemMessage(): string
            {
                return 'System message.';
            }

            public function getUserMessage(): string
            {
                return 'Hello {{name}}. Welcome, {{name}}.';
            }

            public function getSchema(): ?array
            {
                return null;
            }
        };

        $messages = $renderer->render($template, ['name' => 'Ali']);

        $this->assertSame('Hello Ali. Welcome, Ali.', $messages[1]['content']);
    }


    public function test_it_replaces_null_with_an_empty_string(): void
    {
        $renderer = new PromptRenderer();

        $template = new class implements PromptTemplate {
            public function getSystemMessage(): string
            {
                return 'System message.';
            }

            public function getUserMessage(): string
            {
                return 'Optional description: {{description}}';
            }

            public function getSchema(): ?array
            {
                return null;
            }
        };

        $messages = $renderer->render($template, ['description' => null]);

        $this->assertSame('Optional description: ', $messages[1]['content']);
    }


    public function test_it_converts_boolean_value_to_explicit_strings(): void
    {
        $renderer = new PromptRenderer();

        $template = new class implements PromptTemplate {
            public function getSystemMessage(): string
            {
                return 'System message.';
            }

            public function getUserMessage(): string
            {
                return 'Enabled: {{enabled}}, archived: {{archived}}';
            }

            public function getSchema(): ?array
            {
                return null;
            }
        };

        $messages = $renderer->render($template, ['enabled' => true, 'archived' => false]);

        $this->assertSame('Enabled: true, archived: false', $messages[1]['content']);
    }
}
