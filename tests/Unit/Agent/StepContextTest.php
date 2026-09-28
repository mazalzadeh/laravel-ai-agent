<?php

namespace Tests\Unit\Agent;

use App\Agent\Context\StepContext;
use PHPUnit\Framework\TestCase;

class StepContextTest extends TestCase
{
    public function test_it_stores_steps_and_exports_as_array(): void
    {
        $ctx = new StepContext();

        $ctx->addStep(name: 'decision', input: 'Q1', output: 'DIRECT_ANSWER', decision: ['action' => 'DIRECT_ANSWER']);
        $ctx->addStep(name: 'final_answer', input: 'Q1', output: 'A1');

        $data = $ctx->toArray();

        $this->assertArrayHasKey('steps', $data);
        $this->assertCount(2, $data['steps']);
        $this->assertSame('decision', $data['steps'][0]['name']);
        $this->assertSame('final_answer', $data['steps'][1]['name']);
    }


    public function test_it_builds_prompt_context_from_recent_steps_only(): void
    {
        $ctx = new StepContext(maxStepsForPrompt: 2);

        $ctx->addStep(name: 's1', input: 'i1', output: 'o1');
        $ctx->addStep(name: 's2', input: 'i2', output: 'o2');
        $ctx->addStep(name: 's3', input: 'i3', output: 'o3');

        $prompt = $ctx->toPromptContext();

        $this->assertStringNotContainsString('s1', $prompt);
        $this->assertStringContainsString('s2', $prompt);
        $this->assertStringContainsString('s3', $prompt);
    }
}
