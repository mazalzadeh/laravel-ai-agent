<?php

namespace Tests\Unit\Agent;

use App\Agent\DTO\DecisionResult;
use App\Agent\Enums\AgentAction;
use App\Agent\Parsers\DecisionOutputParser;
use PHPUnit\Framework\TestCase;

class DecisionOutputParserTest extends TestCase
{
    private DecisionOutputParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new DecisionOutputParser();
    }


    public function test_parses_clean_json_into_decision_result(): void
    {
        $rawOutput = json_encode([
            'action' => AgentAction::SEARCH_KNOWLEDGE_BASE->value,
            'parameters' => ['query' => 'German language requirements'],
            'reasoning' => 'The user is asking about specific migration guidelines.'
        ], JSON_THROW_ON_ERROR);

        $result = $this->parser->parse($rawOutput);

        $this->assertInstanceOf(DecisionResult::class, $result);
        $this->assertEquals(AgentAction::SEARCH_KNOWLEDGE_BASE->value, $result->action);
        $this->assertEquals(['query' => 'German language requirements'], $result->parameters);
        $this->assertEquals('The user is asking about specific migration guidelines.', $result->reasoning);
    }


    public function test_parses_json_wrapped_in_markdown_code_blocks(): void
    {
        $rawOutput = "Here is my decision:\n
        ```json\n" . json_encode([
            'action' => AgentAction::DIRECT_ANSWER->value,
            'parameters' => ['answer' => 'Hello there!'],
            'reasoning' => 'User greeted casually.',
        ]) . "\n
        ```\nHope this helps!";

        $result = $this->parser->parse($rawOutput);

        $this->assertEquals(AgentAction::DIRECT_ANSWER->value, $result->action);
        $this->assertEquals(['answer' => 'Hello there!'], $result->parameters);
        $this->assertEquals('User greeted casually.', $result->reasoning);
    }


    public function test_falls_back_gracefully_on_invalid_json(): void
    {
        $rawOutput = 'I am not sure, just answering directly.';

        $result = $this->parser->parse($rawOutput);

        $this->assertInstanceOf(DecisionResult::class, $result);
        $this->assertEquals(AgentAction::DIRECT_ANSWER->value, $result->action);
        $this->assertEquals(['raw_content' => $rawOutput], $result->parameters);
        $this->assertStringContainsString('Fallback', $result->reasoning);
    }
}
