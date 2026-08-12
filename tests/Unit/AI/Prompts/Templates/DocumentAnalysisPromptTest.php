<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Prompts\Templates;

use App\AI\Prompts\Contracts\PromptTemplate;
use App\AI\Prompts\Templates\DocumentAnalysisPrompt;
use App\AI\Prompts\PromptRenderer;
use PHPUnit\Framework\TestCase;

class DocumentAnalysisPromptTest extends TestCase
{
    public function test_it_implements_the_prompt_template_contract(): void
    {
        $prompt = new DocumentAnalysisPrompt();

        $this->assertInstanceOf(PromptTemplate::class, $prompt);
    }


    public function test_it_provides_a_document_analysis_system_message(): void
    {
        $prompt = new DocumentAnalysisPrompt();

        $this->assertSame(
            'You are an expert document analyst. Analyze the provided document accurately and return only valid JSON.',
            $prompt->getSystemMessage()
        );
    }


    public function test_it_provides_a_user_message_with_a_document_placeholder(): void
    {
        $prompt = new DocumentAnalysisPrompt();

        $this->assertStringContainsString(
            '{{document}}',
            $prompt->getUserMessage()
        );
    }


    public function test_it_provides_a_structured_output_schema(): void
    {
        $prompt = new DocumentAnalysisPrompt();

        $this->assertSame([
            'required' =>
            [
                'summary',
                'category',
            ],
            'properties' =>
            [
                'summary' =>
                [
                    'type' => 'string',
                ],
                'category' =>
                [
                    'type' => 'string',
                ],
            ],
        ], $prompt->getSchema());
    }


    public function test_it_can_be_rendered_with_document_content(): void
    {
        $renderer = new PromptRenderer();
        $prompt = new DocumentAnalysisPrompt();


        $messages = $renderer->render($prompt, ['document' => 'Laravel is a PHP web application framework.']);

        $this->assertSame(
            $prompt->getSystemMessage(),
            $messages[0]['content']
        );


        $this->assertStringContainsString('Laravel is a PHP web application framework.', $messages[1]['content']);

        $this->assertStringNotContainsString('{{document}}', $messages[1]['content']);
    }
}
