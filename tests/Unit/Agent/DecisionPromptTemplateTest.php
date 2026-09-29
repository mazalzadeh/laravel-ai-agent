<?php

namespace Tests\Unit\Agent;

use App\Agent\Enums\AgentAction;
use App\Agent\Prompts\DecisionPromptTemplate;
use PHPUnit\Framework\TestCase;

class DecisionPromptTemplateTest extends TestCase
{
    private DecisionPromptTemplate $promptTemplate;

    public function setUp(): void
    {
        parent::setUp();
        $this->promptTemplate = new DecisionPromptTemplate();
    }

    public function test_renders_system_prompt_with_available_actions(): void
    {
        $systemPrompt = $this->promptTemplate->renderSystemPrompt();

        $this->assertNotEmpty($systemPrompt);
        $this->assertStringContainsString('JSON format', $systemPrompt);

        // Ensure all enum actions are documented in the generated prompt
        foreach (AgentAction::cases() as $action) {
            $this->assertStringContainsString($action->value, $systemPrompt);
        }
    }


    public function test_builds_user_prompt_with_input_and_optional_context(): void
    {
        $userInput = 'What are the visa requirements for Germany?';
        $context = 'User is currently holding a B1 German certificate.';

        $userPrompt = $this->promptTemplate->renderUserPrompt($userInput, $context);

        $this->assertStringContainsString($userInput, $userPrompt);
        $this->assertStringContainsString($context, $userPrompt);
    }


    public function test_builds_user_prompt_without_context_cleanly(): void
    {
        $userInput = 'Hi there!';

        $userPrompt = $this->promptTemplate->renderUserPrompt($userInput);

        $this->assertStringContainsString($userInput, $userPrompt);
        $this->assertStringNotContainsString('context:', $userPrompt);
    }


    public function test_it_includes_step_history_in_prompt_when_provided(): void
    {
        $template = new DecisionPromptTemplate();
        $history = "[Step 1] decision\nInput: hello\nOutput: DIRECT_ANSWER";

        $prompt = $template->renderUserPrompt(
            userInput: 'What is next?',
            context: 'Some context',
            stepHistory: $history
        );

        $this->assertStringContainsString('Previous Steps History:', $prompt);
        $this->assertStringContainsString($history, $prompt);
    }


    public function test_it_omits_step_history_section_when_empty_or_null(): void
    {
        $template = new DecisionPromptTemplate();

        $prompt = $template->renderUserPrompt(userInput: 'What is next?');

        $this->assertStringNotContainsString('Previous Steps History:', $prompt);
    }
}
