<?php

namespace Tests\Unit\Agent;

use App\Agent\Enums\AgentAction;
use PHPUnit\Framework\TestCase;

class AgentActionTest extends TestCase
{
    public function test_enum_cases_have_expected_values(): void
    {
        $this->assertEquals('DIRECT_ANSWER', AgentAction::DIRECT_ANSWER->value);
        $this->assertEquals('SEARCH_KNOWLEDGE_BASE', AgentAction::SEARCH_KNOWLEDGE_BASE->value);
        $this->assertEquals('DECOMPOSE_TASK', AgentAction::DECOMPOSE_TASK->value);
    }


    public function test_each_case_provides_a_description(): void
    {
        $this->assertNotEmpty(AgentAction::DIRECT_ANSWER->description());
        $this->assertNotEmpty(AgentAction::SEARCH_KNOWLEDGE_BASE->description());
        $this->assertNotEmpty(AgentAction::DECOMPOSE_TASK->description());
    }

    public function test_can_retrieve_all_actions_with_descriptions(): void
    {
        $options = AgentAction::optionsForPrompt();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('DIRECT_ANSWER', $options);
        $this->assertArrayHasKey('SEARCH_KNOWLEDGE_BASE', $options);
        $this->assertArrayHasKey('DECOMPOSE_TASK', $options);
    }
}
