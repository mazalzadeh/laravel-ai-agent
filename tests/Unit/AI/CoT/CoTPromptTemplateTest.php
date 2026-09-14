<?php

declare(strict_types=1);

namespace Tests\Unit\AI\CoT;

use App\AI\CoT\Prompts\CoTPromptTemplate;
use PHPUnit\Framework\TestCase;

class CoTPromptTemplateTest extends TestCase
{
    private CoTPromptTemplate $template;


    protected function setUp(): void
    {
        parent::setUp();
        $this->template = new CoTPromptTemplate();
    }


    public function test_prompt_contains_cot_tag_instructions(): void
    {
        $rendered = $this->template->render([
            'question' => 'What is the sum of angles in a triangle?',
        ]);

        $this->assertStringContainsString('<thought>', $rendered);
        $this->assertStringContainsString('</thought>', $rendered);
        $this->assertStringContainsString('<answer>', $rendered);
        $this->assertStringContainsString('</answer>', $rendered);
        $this->assertStringContainsString('What is the sum of angles in a triangle?', $rendered);
    }


    public function test_prompt_includes_context_when_procided(): void
    {
        $rendered = $this->template->render([
            'question' => 'What is company revenue?',
            'context' => 'Company revenue in 2025 was $10M.',
        ]);

        $this->assertStringContainsString('Company revenue in 2025 was $10M.', $rendered);
        $this->assertStringContainsString('What is company revenue?', $rendered);
    }


    public function test_prompt_handles_missing_context_cleanly(): void
    {
        $rendered = $this->template->render([
            'question' => 'Explain gravity simply.',
        ]);

        $this->assertStringContainsString('Explain gravity simply.', $rendered);
        $this->assertStringNotContainsString('Context Information:', $rendered);
    }
}
