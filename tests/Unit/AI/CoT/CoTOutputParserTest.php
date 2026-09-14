<?php


declare(strict_types=1);

namespace Tests\Unit\AI\CoT;

use App\AI\CoT\CoTOutputParser;
use App\AI\CoT\DTOs\CoTResult;
use PHPUnit\Framework\TestCase;

class CoTOutputParserTest extends TestCase
{
    private CoTOutputParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new CoTOutputParser();
    }


    public function test_parses_valid_cot_output_correctly(): void
    {
        $rawOutput = <<<TEXT
                        <thought>
                        Step 1: Calculate the profit.
                        Step 2: Apply taxes.
                        Final: 50 million.
                        </thought>
                        <answer>
                        The net profit for your company is 50 million IRR.
                        </answer>
                    TEXT;

        $result = $this->parser->parse($rawOutput);

        $this->assertInstanceOf(CoTResult::class, $result);
        $this->assertStringContainsString('Step 1: Calculate the profit.', $result->getReasoning());
        $this->assertEquals('The net profit for your company is 50 million IRR.', $result->getAnswer());
        $this->assertTrue($result->hasReasoning());
    }


    public function test_parses_tags_with_case_insenssitivity_and_whitespace(): void
    {
        $rawOutput = <<<TEXT
                        <THOUGHT>
                            Internal reasoning here.
                        </THOUGHT>

                        <ANSWER>
                            Clean answer here.
                        </ANSWER>
                    TEXT;

        $result = $this->parser->parse($rawOutput);

        $this->assertEquals('Internal reasoning here.', $result->getReasoning());
        $this->assertEquals('Clean answer here.', $result->getAnswer());
    }


    public function test_fallback_when_tags_are_missing(): void
    {
        $rawOutput = 'Plain text answer without any structured tags.';

        $result = $this->parser->parse($rawOutput);

        $this->assertInstanceOf(CoTResult::class, $result);
        $this->assertNull($result->getReasoning());
        $this->assertEquals('Plain text answer without any structured tags.', $result->getAnswer());
        $this->assertFalse($result->hasReasoning());
    }


    public function test_handles_missing_answer_tag_gracefully(): void
    {
        $rawOutput = <<<TEXT
                        <thought>
                            Only reasoning exists here.
                        </thought>
                    TEXT;

        $result = $this->parser->parse($rawOutput);

        $this->assertEquals('Only reasoning exists here.', $result->getReasoning());
        $this->assertEquals('', $result->getAnswer());
    }
}
